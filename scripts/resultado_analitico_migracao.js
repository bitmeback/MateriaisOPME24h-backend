#!/usr/bin/env node
// Carga histórica robusta: usa apenas colunas confirmadas na DAX
// Campos extras da tabela local recebem NULL até validação manual.

const https = require('https');
const mysql = require('mysql2/promise');

const CURRENT_YEAR = new Date().getFullYear();
const CURRENT_MONTH = new Date().getMonth() + 1;
const START_YEAR = 2024;
const START_MONTH = 1;

function makeRequest(url, options = {}) {
  return new Promise((resolve, reject) => {
    const req = https.request(url, options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try { resolve({ status: res.statusCode, body: JSON.parse(data) }); }
        catch(e) { resolve({ status: res.statusCode, body: data }); }
      });
    });
    req.on('error', reject);
    if (options.body) req.write(options.body);
    req.end();
  });
}

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function safeStr(v) {
  return v != null ? String(v).trim() : null;
}

(async () => {
  const baseUrl = 'https://donahelena.reportload.com';
  const workspaceId = '0a4c534a-f8ef-4b3f-a842-4982c842b41c';
  const datasetId = '70a003f4-30ff-49a2-8991-deba110f7455';

  console.log('[MIGRACAO] INICIANDO CARGA HISTÓRICA: jan/2024 até', CURRENT_MONTH + '/' + CURRENT_YEAR);

  console.log('[API] Autenticando no ReportLoad...');
  const loginRes = await makeRequest(baseUrl + '/api/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: 'compras4@donahelena.com.br', password: '@D3z3mbr0%', language: 'pt-BR' })
  });

  if (loginRes.status !== 200 || !loginRes.body.token) {
    console.error('[ERRO] Falha no login ReportLoad');
    process.exit(1);
  }

  console.log('[API] Obtendo embedded token...');
  const reportViewRes = await makeRequest(baseUrl + '/api/auth/report_view/report-view?id=c498464e-67a3-43f0-8c22-2dbb5efe1048', {
    method: 'GET',
    headers: { 'Authorization': 'Bearer ' + loginRes.body.token, 'Content-Type': 'application/json' }
  });
  const embeddedToken = reportViewRes.body?.token?.token;
  if (!embeddedToken) {
    console.error('[ERRO] Falha no embedded token');
    process.exit(1);
  }

  console.log('[DB] Conectando ao MariaDB...');
  const connection = await mysql.createConnection({
    socketPath: '/var/run/mysqld/mysqld.sock',
    user: 'root',
    password: 'QbbRkK7bKiGFMRCWggiLgaiu',
    database: 'materiais_opme'
  });

  let totalInserted = 0;

  for (let year = START_YEAR; year <= CURRENT_YEAR; year++) {
    const monthEnd = year === CURRENT_YEAR ? CURRENT_MONTH : 12;

    for (let month = START_MONTH; month <= monthEnd; month++) {
      console.log('\n============================');
      console.log(`[DAX] Carregando: ${month.toString().padStart(2, '0')}/${year}`);

      const daxQuery = `
        EVALUATE
        SUMMARIZECOLUMNS(
            'OPME'[NR_ATENDIMENTO],
            'OPME'[NR_CIRURGIA],
            'OPME'[CD_MATERIAL],
            'OPME'[DS_MATERIAL],
            'OPME'[QT_MATERIAL],
            'OPME'[VL_MATERIAL_CONTA],
            'OPME'[VL_ULTIMA_COMPRA],
            'OPME'[VL_LUCRO_LIQ],
            'OPME'[DS_FORNECEDOR],
            'OPME'[SITUACAO],
            'OPME'[DT_TERMINO],
            'OPME'[DT_BAIXA],
            'OPME'[DS_CONVENIO],
            'OPME'[DS_SETOR],
            FILTER('OPME', YEAR('OPME'[DT_TERMINO]) = ${year} && MONTH('OPME'[DT_TERMINO]) = ${month})
        )
      `;

      const queryRes = await makeRequest(`https://api.powerbi.com/v1.0/myorg/groups/${workspaceId}/datasets/${datasetId}/executeQueries`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + embeddedToken, 'Content-Type': 'application/json' },
        body: JSON.stringify({ queries: [{ query: daxQuery }] })
      });

      const rows = queryRes.body?.results?.[0]?.tables?.[0]?.rows || [];

      if (queryRes.status !== 200) {
        console.error(`[ERRO] DAX falhou em ${month}/${year}:`, JSON.stringify(queryRes.body).substring(0, 300));
        continue;
      }

      console.log(`[API] Retornou ${rows.length} registros`);

      if (rows.length === 0) {
        console.log('[DB] Mês vazio, nada para inserir.');
        continue;
      }

      const valuesArray = [];
      for (const r of rows) {
        valuesArray.push([
          r['OPME[NR_ATENDIMENTO]'] || null,
          r['OPME[NR_CIRURGIA]'] || null,
          r['OPME[CD_MATERIAL]'] != null ? Number(r['OPME[CD_MATERIAL]']) : null,
          safeStr(r['OPME[DS_MATERIAL]']),
          r['OPME[QT_MATERIAL]'] != null ? Number(r['OPME[QT_MATERIAL]']) : null,
          r['OPME[VL_MATERIAL_CONTA]'] != null ? Number(r['OPME[VL_MATERIAL_CONTA]']) : null,
          r['OPME[VL_ULTIMA_COMPRA]'] != null ? Number(r['OPME[VL_ULTIMA_COMPRA]']) : null,
          r['OPME[VL_LUCRO_LIQ]'] != null ? Number(r['OPME[VL_LUCRO_LIQ]']) : null,
          safeStr(r['OPME[DS_FORNECEDOR]']),
          null,
          safeStr(r['OPME[SITUACAO]']),
          r['OPME[DT_TERMINO]'] || null,
          r['OPME[DT_BAIXA]'] || null,
          safeStr(r['OPME[DS_CONVENIO]']),
          safeStr(r['OPME[DS_SETOR]']),
          null,
          null,
          null,
          null,
          null,
          null,
          null,
          null,
          null,
          null,
          null,
          year,
          month
        ]);
      }

      await connection.execute('DELETE FROM resultado_analitico WHERE ano = ? AND mes = ?', [year, month]);

      const chunkSize = 2000;
      for (let i = 0; i < valuesArray.length; i += chunkSize) {
        const ch = valuesArray.slice(i, i + chunkSize);
        await connection.query(
          `INSERT INTO resultado_analitico (
            nr_atendimento, nr_cirurgia, cd_material, ds_material, qtde, vl_conta, vl_ultima_compra, vl_lucro_liq,
            ds_fornecedor, cd_fornec_consignado, situacao, dt_termino, dt_baixa,
            ds_convenio, ds_setor, ds_motivo_baixa, ds_carater, ie_pacote, tipo,
            vl_taxa_proporcional, vl_imposto, vl_oc,
            nr_interno_conta, nr_nota_fiscal_ent_consignado,
            dt_envio_prot_fatur, dt_receb_prot_fatur, ano, mes
          ) VALUES ?`,
          [ch]
        );
      }

      totalInserted += valuesArray.length;
      console.log(`[DB] Inseridos ${valuesArray.length} registros para ${month}/${year}`);
      await delay(1200);
    }
  }

  await connection.end();
  console.log(`\n[CONCLUIDO] Carga histórica finalizada. Total inserido: ${totalInserted}`);
})();
