<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Support/Auth.php';
auth_require_permission('matrices.view');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Constructor ISO de juguetes y ensayos sin repetir</title>
  <style>
    :root {
      --bg: #f5f7fb;
      --surface: #ffffff;
      --surface-soft: #f8fafc;
      --text: #172033;
      --muted: #667085;
      --line: #dde5ef;
      --primary: #2258d6;
      --primary-700: #1744aa;
      --primary-soft: #e9efff;
      --ok: #0f8a5f;
      --warn: #b54708;
      --danger: #b42318;
      --shadow: 0 18px 45px rgba(23, 32, 51, 0.08);
      --radius: 18px;
      --radius-sm: 12px;
      --mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      --sans: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    * { box-sizing: border-box; }

    html { scroll-behavior: smooth; }

    body {
      margin: 0;
      font-family: var(--sans);
      background:
        radial-gradient(circle at top left, rgba(34, 88, 214, .16), transparent 34rem),
        linear-gradient(180deg, #fbfcff 0%, var(--bg) 100%);
      color: var(--text);
      min-height: 100vh;
    }

    button, select, input {
      font: inherit;
    }

    button {
      border: 0;
      border-radius: 999px;
      padding: .76rem 1rem;
      cursor: pointer;
      background: var(--primary);
      color: #fff;
      font-weight: 700;
      transition: transform .15s ease, box-shadow .15s ease, background .15s ease, opacity .15s ease;
      box-shadow: 0 10px 24px rgba(34, 88, 214, .18);
    }

    button:hover { transform: translateY(-1px); background: var(--primary-700); }
    button:active { transform: translateY(0); }
    button:disabled { opacity: .45; cursor: not-allowed; transform: none; }

    button.secondary {
      color: var(--text);
      background: #eef2f7;
      box-shadow: none;
    }

    button.secondary:hover { background: #e4eaf2; }

    button.ghost {
      color: var(--primary);
      background: transparent;
      border: 1px solid var(--line);
      box-shadow: none;
    }

    button.danger {
      color: #fff;
      background: var(--danger);
      box-shadow: 0 10px 24px rgba(180, 35, 24, .15);
    }

    label {
      display: block;
      font-weight: 750;
      margin-bottom: .45rem;
      color: #273246;
    }

    input[type="text"], select {
      width: 100%;
      border: 1px solid var(--line);
      background: #fff;
      color: var(--text);
      padding: .8rem .9rem;
      border-radius: var(--radius-sm);
      outline: none;
      transition: border .15s ease, box-shadow .15s ease;
    }

    input[type="text"]:focus, select:focus {
      border-color: rgba(34, 88, 214, .55);
      box-shadow: 0 0 0 4px rgba(34, 88, 214, .11);
    }

    .hero {
      padding: 2rem 1rem 1.25rem;
    }

    .hero-inner, main, footer {
      max-width: 1280px;
      margin: 0 auto;
    }

    .hero-card {
      background: rgba(255, 255, 255, .82);
      border: 1px solid rgba(221, 229, 239, .9);
      box-shadow: var(--shadow);
      backdrop-filter: blur(10px);
      border-radius: 28px;
      padding: clamp(1.2rem, 2.5vw, 2.2rem);
      overflow: hidden;
      position: relative;
    }

    .hero-card::after {
      content: "";
      position: absolute;
      inset: auto -8rem -8rem auto;
      width: 20rem;
      height: 20rem;
      background: rgba(34, 88, 214, .08);
      border-radius: 50%;
      pointer-events: none;
    }

    .eyebrow {
      color: var(--primary);
      text-transform: uppercase;
      letter-spacing: .12em;
      font-size: .76rem;
      font-weight: 850;
      margin: 0 0 .75rem;
    }

    h1 {
      margin: 0;
      font-size: clamp(2rem, 4vw, 4.1rem);
      line-height: 1.02;
      max-width: 920px;
      letter-spacing: -.05em;
    }

    .hero-card > p:not(.eyebrow) {
      max-width: 860px;
      color: var(--muted);
      font-size: 1.08rem;
      line-height: 1.65;
      margin: 1rem 0 0;
    }

    main {
      padding: 0 1rem 2rem;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(6, minmax(0, 1fr));
      gap: .8rem;
      margin-top: 1.25rem;
      position: relative;
      z-index: 1;
    }

    .stat {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: .9rem;
      min-height: 86px;
    }

    .stat strong {
      display: block;
      font-size: 1.55rem;
      letter-spacing: -.04em;
    }

    .stat span {
      display: block;
      color: var(--muted);
      font-size: .82rem;
      margin-top: .15rem;
    }

    .panel {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: 0 12px 34px rgba(23, 32, 51, .045);
      padding: clamp(1rem, 1.9vw, 1.45rem);
      margin-top: 1rem;
    }

    .panel h2, .panel h3 {
      margin: 0 0 .85rem;
      letter-spacing: -.03em;
    }

    .panel h2 {
      font-size: 1.25rem;
    }

    .panel h3 {
      font-size: 1.05rem;
    }

    .hint {
      color: var(--muted);
      line-height: 1.55;
      margin: -.25rem 0 1rem;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
      gap: 1rem;
    }

    .grid-3 {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: .75rem;
    }

    .form-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr);
      gap: .75rem;
      margin: .85rem 0;
    }

    .actions {
      display: flex;
      gap: .7rem;
      flex-wrap: wrap;
      align-items: center;
      margin-top: .85rem;
    }

    .combo-summary {
      border: 1px solid var(--line);
      background: var(--surface-soft);
      border-radius: var(--radius-sm);
      padding: .85rem;
      color: var(--muted);
      min-height: 78px;
      line-height: 1.5;
    }

    .combo-summary strong { color: var(--text); }

    .pill-list {
      display: flex;
      flex-wrap: wrap;
      gap: .5rem;
      margin-top: .6rem;
    }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      border-radius: 999px;
      background: var(--primary-soft);
      color: var(--primary-700);
      padding: .38rem .62rem;
      font-weight: 750;
      font-size: .83rem;
      line-height: 1.2;
    }

    .pill.neutral {
      color: #344054;
      background: #eef2f7;
    }

    .pill.ok {
      color: #067647;
      background: #dcfae6;
    }

    .pill.warn {
      color: #b54708;
      background: #fff3e6;
    }

    .pill button {
      padding: 0;
      background: transparent;
      color: inherit;
      box-shadow: none;
      font-weight: 900;
      line-height: 1;
    }

    .checkbox-grid {
      display: grid;
      gap: .55rem;
      max-height: 310px;
      overflow: auto;
      border: 1px solid var(--line);
      border-radius: var(--radius-sm);
      padding: .6rem;
      background: var(--surface-soft);
    }

    .check-card {
      display: grid;
      grid-template-columns: auto 1fr;
      gap: .55rem;
      align-items: flex-start;
      padding: .7rem;
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #fff;
      cursor: pointer;
    }

    .check-card input {
      margin-top: .2rem;
      width: 1.05rem;
      height: 1.05rem;
      accent-color: var(--primary);
    }

    .check-card strong {
      display: block;
      font-size: .94rem;
      line-height: 1.3;
    }

    .check-card span {
      display: block;
      color: var(--muted);
      font-size: .8rem;
      margin-top: .2rem;
    }

    .current-builder {
      display: grid;
      grid-template-columns: minmax(0, .7fr) minmax(0, 1.3fr);
      gap: 1rem;
    }

    .toy-card {
      background: var(--surface-soft);
      border: 1px solid var(--line);
      border-radius: var(--radius-sm);
      padding: .9rem;
    }

    .toy-card header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: .75rem;
      margin-bottom: .5rem;
    }

    .toy-card h3 {
      margin: 0;
      font-size: 1rem;
    }

    .toy-card small {
      color: var(--muted);
      display: block;
      margin-top: .15rem;
    }

    .saved-list {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: .75rem;
    }

    .empty {
      border: 1px dashed #c8d3e3;
      border-radius: var(--radius-sm);
      padding: 1rem;
      color: var(--muted);
      background: #fbfdff;
      text-align: center;
      line-height: 1.5;
    }

    .results-toolbar {
      display: grid;
      grid-template-columns: minmax(0, 1.4fr) minmax(0, .55fr) minmax(0, .75fr);
      gap: .7rem;
      margin: 1rem 0;
    }

    .result-stats {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: .65rem;
      margin-top: 1rem;
    }

    .result-stat {
      border-radius: 16px;
      border: 1px solid var(--line);
      background: var(--surface-soft);
      padding: .85rem;
    }

    .result-stat strong {
      display: block;
      font-size: 1.3rem;
    }

    .result-stat span {
      color: var(--muted);
      font-size: .82rem;
    }

    .table-wrap {
      overflow: auto;
      border: 1px solid var(--line);
      border-radius: 16px;
      background: #fff;
      max-height: 70vh;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 980px;
    }

    th, td {
      padding: .78rem .8rem;
      border-bottom: 1px solid var(--line);
      text-align: left;
      vertical-align: top;
      font-size: .9rem;
    }

    th {
      position: sticky;
      top: 0;
      background: #f7f9fc;
      z-index: 1;
      color: #344054;
      font-size: .78rem;
      text-transform: uppercase;
      letter-spacing: .06em;
    }

    td:nth-child(1) {
      width: 58px;
      color: var(--muted);
      font-family: var(--mono);
    }

    .essay-name {
      font-weight: 800;
      color: #182230;
    }

    .muted {
      color: var(--muted);
    }

    .mini {
      font-size: .78rem;
      color: var(--muted);
      line-height: 1.45;
    }

    .toast {
      position: fixed;
      right: 1rem;
      bottom: 1rem;
      background: #101828;
      color: #fff;
      border-radius: 999px;
      padding: .8rem 1rem;
      box-shadow: var(--shadow);
      opacity: 0;
      pointer-events: none;
      transform: translateY(10px);
      transition: opacity .18s ease, transform .18s ease;
      z-index: 10;
      max-width: min(440px, calc(100vw - 2rem));
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    footer {
      padding: 0 1rem 2rem;
      color: var(--muted);
      font-size: .9rem;
      line-height: 1.6;
    }

    @media (max-width: 1050px) {
      .stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      .grid-2, .current-builder { grid-template-columns: 1fr; }
      .saved-list { grid-template-columns: 1fr; }
      .result-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .results-toolbar { grid-template-columns: 1fr; }
    }

    @media (max-width: 620px) {
      .stats, .grid-3 { grid-template-columns: 1fr; }
      .actions button { width: 100%; }
      .hero { padding-top: 1rem; }
      .hero-card { border-radius: 20px; }
    }

    @media print {
      body { background: #fff; }
      .hero, .builder-panel, .selection-panel, .saved-panel, .actions, .results-toolbar, footer, .toast { display: none !important; }
      main { max-width: none; padding: 0; }
      .panel { box-shadow: none; border: 0; }
      .table-wrap { max-height: none; overflow: visible; border: 0; }
      table { min-width: 0; font-size: 10px; }
      th { position: static; }
    }
  </style>
</head>
<body>
  <header class="hero">
    <div class="hero-inner">
      <section class="hero-card">
        <p class="eyebrow">Mini proyecto HTML · Esquema ISO</p>
        <h1>Constructor de juguetes y ensayos sin repetir</h1>
        <p>
          Selecciona la clase principal, carga combinaciones ya definidas o arma manualmente cada juguete por subfamilia. 
          Al final, la herramienta consolida los ensayos requeridos y elimina duplicados por nombre de ensayo, agrupando literales, normas, fases y fuentes.
        </p>
        <div class="stats" id="stats"></div>
      </section>
    </div>
  </header>

  <main>
    <section class="panel">
      <h2>Flujo recomendado</h2>
      <p class="hint">
        Usa el modo guiado cuando quieras partir de una combinación ISO existente. Usa el modo manual cuando un juguete tenga características mixtas.
        Puedes guardar varios juguetes y calcular una lista final de ensayos únicos para todo el lote.
      </p>
      <div class="pill-list" id="metaNotes"></div>
    </section>

    <section class="grid-2 selection-panel">
      <article class="panel">
        <h2>1. Selección guiada por clasificación ISO</h2>
        <p class="hint">Elige una combinación de clases principales y luego una subcombinación para cargarla al juguete actual.</p>

        <div class="form-row">
          <div>
            <label for="classComboSelect">Clase principal / combinación de clases</label>
            <select id="classComboSelect"></select>
          </div>

          <div id="classComboInfo" class="combo-summary"></div>

          <div>
            <label for="subComboSelect">Subcombinaciones disponibles</label>
            <select id="subComboSelect"></select>
          </div>

          <div id="subComboInfo" class="combo-summary"></div>
        </div>

        <div class="actions">
          <button type="button" id="loadSubcomboBtn">Agregar al juguete actual</button>
          <button type="button" class="secondary" id="saveSubcomboToyBtn">Crear juguete directo</button>
        </div>
      </article>

      <article class="panel">
        <h2>2. Selección manual por clase principal</h2>
        <p class="hint">Selecciona una clase/familia y marca una o varias subfamilias para sumarlas al juguete actual.</p>

        <div class="form-row">
          <div>
            <label for="familySelect">Clase principal / familia</label>
            <select id="familySelect"></select>
          </div>

          <div>
            <label>Subfamilias disponibles</label>
            <div class="checkbox-grid" id="subfamilyChecks"></div>
          </div>
        </div>

        <div class="actions">
          <button type="button" id="addManualBtn">Agregar subfamilias seleccionadas</button>
          <button type="button" class="secondary" id="clearChecksBtn">Desmarcar</button>
        </div>
      </article>
    </section>

    <section class="panel builder-panel">
      <h2>3. Constructor del juguete actual</h2>
      <div class="current-builder">
        <div>
          <label for="toyNameInput">Nombre del juguete</label>
          <input id="toyNameInput" type="text" placeholder="Ej. Carro plástico con imanes + cuerda">
          <p class="hint">Este nombre aparecerá como fuente en el resultado final.</p>
          <div class="actions">
            <button type="button" id="saveCurrentToyBtn">Guardar juguete</button>
            <button type="button" class="secondary" id="resetCurrentBtn">Nuevo juguete</button>
          </div>
        </div>
        <div class="toy-card">
          <header>
            <div>
              <h3>Componentes seleccionados</h3>
              <small id="currentToyCount">0 subfamilias</small>
            </div>
          </header>
          <div id="currentToyChips" class="pill-list"></div>
        </div>
      </div>
    </section>

    <section class="panel saved-panel">
      <h2>4. Juguetes guardados</h2>
      <p class="hint">Puedes calcular con los juguetes guardados y también con el juguete actual si todavía tiene componentes seleccionados.</p>
      <div id="savedToys" class="saved-list"></div>
      <div class="actions">
        <button type="button" id="calculateBtn">Calcular ensayos sin repetir</button>
        <button type="button" class="danger" id="clearAllBtn">Limpiar proyecto</button>
      </div>
    </section>

    <section class="panel results-panel" id="resultsPanel">
      <h2>5. Resultado final: ensayos consolidados sin repetir</h2>
      <p class="hint" id="resultHint">Agrega al menos un juguete y presiona “Calcular ensayos sin repetir”.</p>

      <div class="result-stats" id="resultStats"></div>

      <div class="results-toolbar">
        <input id="searchInput" type="text" placeholder="Buscar por ensayo, literal, norma o subfamilia...">
        <select id="phaseFilter">
          <option value="ALL">Todas las fases</option>
          <option value="OT">OT</option>
          <option value="VIG_1">Vigilancia 1</option>
          <option value="VIG_2">Vigilancia 2</option>
          <option value="RENOVACION">Renovación</option>
        </select>
        <select id="toyFilter">
          <option value="ALL">Todos los juguetes</option>
        </select>
      </div>

      <div class="actions">
        <button type="button" class="secondary" id="exportCsvBtn">Exportar CSV</button>
        <button type="button" class="secondary" id="exportJsonBtn">Exportar JSON</button>
        <button type="button" class="ghost" id="printBtn">Imprimir / PDF</button>
      </div>

      <div class="table-wrap" style="margin-top: 1rem;">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Ensayo único</th>
              <th>Literal / numeral</th>
              <th>Norma técnica</th>
              <th>Fases</th>
              <th>Fuentes</th>
              <th>Juguetes</th>
            </tr>
          </thead>
          <tbody id="resultsBody">
            <tr>
              <td colspan="7" class="empty">Sin resultados calculados.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <footer>
    <strong>Regla de deduplicación aplicada:</strong> si el mismo ensayo aparece en varias subfamilias o en varios juguetes, se muestra una sola vez y se agrupan literales, normas, fases y fuentes.
  </footer>

  <div class="toast" id="toast"></div>

  <script type="application/json" id="app-data">{"meta":{"titulo":"Matriz de juguetes: clasificación, subfamilias y ensayos sin duplicidad","fuente":"Pegado text(18).txt","criterio":"Se generaron todas las combinaciones posibles de subfamilias para cada combinacion de clasificacion ya definida.","deduplicacion":"En el detalle, cada ensayo queda una sola vez por ID_Subcombinacion. Si el mismo ensayo aparece en varias subfamilias, se agrupan normas, literales y fuentes.","fases":"OT = Otorgamiento; VIG_1 = Vigilancia 1; VIG_2 = Vigilancia 2; Renovacion = Renovacion.","notaMaterial":"Se dejo la nota Material maximo de 3 meses en los registros. El archivo fuente no trae una columna de fecha de emision o vigencia del material.","stats":{"combinacionesClasificacion":34,"combinacionesSubfamilia":253,"registrosEnsayosUnicos":2870,"subfamiliasBase":33,"familias":14,"ensayosBaseUnicos":44,"registrosBase":249}},"subfamilies":[{"familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","cantidadEnsayosBase":10},{"familia":"Cometas y juguetes voladores","familiaNorm":"Cometas_y_juguetes_voladores","subfamilia":"Cometas y juguetes voladores","subfamiliaNorm":"Cometas_y_juguetes_voladores","cantidadEnsayosBase":7},{"familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Barbas, bigotes o pelucas","subfamiliaNorm":"Barbas_bigotes_o_pelucas","cantidadEnsayosBase":3},{"familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Disfraces y accesorios generales","subfamiliaNorm":"Disfraces_y_accesorios_generales","cantidadEnsayosBase":4},{"familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Mascaras y Cascos","subfamiliaNorm":"Mascaras_y_Cascos","cantidadEnsayosBase":4},{"familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Carpas","subfamiliaNorm":"Carpas","cantidadEnsayosBase":5},{"familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Inflables para piscina","subfamiliaNorm":"Inflables_para_piscina","cantidadEnsayosBase":4},{"familia":"Juegos de experimentos químicos y actividades relacionadas","familiaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","subfamilia":"Juegos de experimentos químicos y actividades relacionadas","subfamiliaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","cantidadEnsayosBase":8},{"familia":"Juegos químicos distintos de los juegos de experimento","familiaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","subfamilia":"Juegos químicos distintos de los juegos de experimento","subfamiliaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","cantidadEnsayosBase":8},{"familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","cantidadEnsayosBase":11},{"familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes de material rígido","subfamiliaNorm":"Juguetes_de_material_rígido","cantidadEnsayosBase":8},{"familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes y accesorios que dejan traza","subfamiliaNorm":"Juguetes_y_accesorios_que_dejan_traza","cantidadEnsayosBase":4},{"familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Lazos y cuerdas de saltar","subfamiliaNorm":"Lazos_y_cuerdas_de_saltar","cantidadEnsayosBase":7},{"familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","cantidadEnsayosBase":10},{"familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","cantidadEnsayosBase":13},{"familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","cantidadEnsayosBase":14},{"familia":"Juguetes que contienen liquido o gel","familiaNorm":"Juguetes_que_contienen_liquido_o_gel","subfamilia":"Juguetes que contienen liquido o gel","subfamiliaNorm":"Juguetes_que_contienen_liquido_o_gel","cantidadEnsayosBase":4},{"familia":"Juguetes Unidos a alimentos","familiaNorm":"Juguetes_Unidos_a_alimentos","subfamilia":"Juguetes Unidos a alimentos","subfamiliaNorm":"Juguetes_Unidos_a_alimentos","cantidadEnsayosBase":9},{"familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","cantidadEnsayosBase":10},{"familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","cantidadEnsayosBase":11},{"familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","cantidadEnsayosBase":16},{"familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables Estacionarios","subfamiliaNorm":"Montables_Estacionarios","cantidadEnsayosBase":8},{"familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","cantidadEnsayosBase":12},{"familia":"Montables","familiaNorm":"Montables","subfamilia":"Sillitas de ruedas y cochecitos de muñecos","subfamiliaNorm":"Sillitas_de_ruedas_y_cochecitos_de_muñecos","cantidadEnsayosBase":9},{"familia":"Muñecos Plásticos Vestidos","familiaNorm":"Muñecos_Plásticos_Vestidos","subfamilia":"Muñecos Plásticos Vestidos","subfamiliaNorm":"Muñecos_Plásticos_Vestidos","cantidadEnsayosBase":8},{"familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Juguetes de material flexible","subfamiliaNorm":"Juguetes_de_material_flexible","cantidadEnsayosBase":5},{"familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","cantidadEnsayosBase":15},{"familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Globos de Latex","subfamiliaNorm":"Globos_de_Latex","cantidadEnsayosBase":3},{"familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Globos plásticos","subfamiliaNorm":"Globos_plásticos","cantidadEnsayosBase":4},{"familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Manillas, gafas que alumbran","subfamiliaNorm":"Manillas_gafas_que_alumbran","cantidadEnsayosBase":4},{"familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Papel y cartón","subfamiliaNorm":"Papel_y_cartón","cantidadEnsayosBase":3},{"familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Pelotas pequeñas y canicas","subfamiliaNorm":"Pelotas_pequeñas_y_canicas","cantidadEnsayosBase":4},{"familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Piñateria plastica","subfamiliaNorm":"Piñateria_plastica","cantidadEnsayosBase":4}],"tests":[{"filaFuente":"2","familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"3","familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","literal":"6.1/6.1.5","ensayo":"Ensayo de Tracción","norma":"EN 71-1 / 8.4.2.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"4","familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"5","familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"6","familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"7","familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","literal":"6.1/6.1.2","ensayo":"Mecanismos de arrastre","norma":"EN 71-1 / 4.10.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"8","familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"9","familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"10","familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"11","familia":"Carros de juguete de cualquier material","familiaNorm":"Carros_de_juguete_de_cualquier_material","subfamilia":"Carros de juguete de cualquier material","subfamiliaNorm":"Carros_de_juguete_de_cualquier_material","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"12","familia":"Cometas y juguetes voladores","familiaNorm":"Cometas_y_juguetes_voladores","subfamilia":"Cometas y juguetes voladores","subfamiliaNorm":"Cometas_y_juguetes_voladores","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"13","familia":"Cometas y juguetes voladores","familiaNorm":"Cometas_y_juguetes_voladores","subfamilia":"Cometas y juguetes voladores","subfamiliaNorm":"Cometas_y_juguetes_voladores","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"14","familia":"Cometas y juguetes voladores","familiaNorm":"Cometas_y_juguetes_voladores","subfamilia":"Cometas y juguetes voladores","subfamiliaNorm":"Cometas_y_juguetes_voladores","literal":"6.1/6.1.1","ensayo":"Ensayo de caída","norma":"EN 71-1 / 8.5","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"15","familia":"Cometas y juguetes voladores","familiaNorm":"Cometas_y_juguetes_voladores","subfamilia":"Cometas y juguetes voladores","subfamiliaNorm":"Cometas_y_juguetes_voladores","literal":"6.1/6.1.9","ensayo":"Resistividad eléctrica de las cuerdas","norma":"EN 71-1 / 8.19","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"16","familia":"Cometas y juguetes voladores","familiaNorm":"Cometas_y_juguetes_voladores","subfamilia":"Cometas y juguetes voladores","subfamiliaNorm":"Cometas_y_juguetes_voladores","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"17","familia":"Cometas y juguetes voladores","familiaNorm":"Cometas_y_juguetes_voladores","subfamilia":"Cometas y juguetes voladores","subfamiliaNorm":"Cometas_y_juguetes_voladores","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"18","familia":"Cometas y juguetes voladores","familiaNorm":"Cometas_y_juguetes_voladores","subfamilia":"Cometas y juguetes voladores","subfamiliaNorm":"Cometas_y_juguetes_voladores","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"19","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Barbas, bigotes o pelucas","subfamiliaNorm":"Barbas_bigotes_o_pelucas","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"20","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Barbas, bigotes o pelucas","subfamiliaNorm":"Barbas_bigotes_o_pelucas","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.2.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"21","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Barbas, bigotes o pelucas","subfamiliaNorm":"Barbas_bigotes_o_pelucas","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"22","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Disfraces y accesorios generales","subfamiliaNorm":"Disfraces_y_accesorios_generales","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"23","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Disfraces y accesorios generales","subfamiliaNorm":"Disfraces_y_accesorios_generales","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"24","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Disfraces y accesorios generales","subfamiliaNorm":"Disfraces_y_accesorios_generales","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"25","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Disfraces y accesorios generales","subfamiliaNorm":"Disfraces_y_accesorios_generales","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"26","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Mascaras y Cascos","subfamiliaNorm":"Mascaras_y_Cascos","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"27","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Mascaras y Cascos","subfamiliaNorm":"Mascaras_y_Cascos","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"28","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Mascaras y Cascos","subfamiliaNorm":"Mascaras_y_Cascos","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.2.4","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"29","familia":"Disfraces y accesorios","familiaNorm":"Disfraces_y_accesorios","subfamilia":"Mascaras y Cascos","subfamiliaNorm":"Mascaras_y_Cascos","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"30","familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Carpas","subfamiliaNorm":"Carpas","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"31","familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Carpas","subfamiliaNorm":"Carpas","literal":"6.1/6.1.7","ensayo":"Juguetes en los que el niño puede penetrar","norma":"EN 71-1/ 4.14.1 (a)","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"32","familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Carpas","subfamiliaNorm":"Carpas","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"33","familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Carpas","subfamiliaNorm":"Carpas","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.4","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"34","familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Carpas","subfamiliaNorm":"Carpas","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"35","familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Inflables para piscina","subfamiliaNorm":"Inflables_para_piscina","literal":"6.1/6.1.6","ensayo":"Juguetes acuáticos e hinchables","norma":"EN 71-1 / 4.18 (b)","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"36","familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Inflables para piscina","subfamiliaNorm":"Inflables_para_piscina","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"37","familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Inflables para piscina","subfamiliaNorm":"Inflables_para_piscina","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"38","familia":"Inflables o que pueden alojar internamente","familiaNorm":"Inflables_o_que_pueden_alojar_internamente","subfamilia":"Inflables para piscina","subfamiliaNorm":"Inflables_para_piscina","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"39","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"40","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"41","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"42","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.1/6.1.1","ensayo":"Ensayo de caída","norma":"EN 71-1 / 8.5","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"43","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.1/6.1.3","ensayo":"Cilindro para partes pequeñas","norma":"EN 71-1 / 8.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"44","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.1/6.1.9","ensayo":"Índice de flujo magnético","norma":"EN 71-1 / 8.35","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"45","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"46","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"47","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"48","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"49","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes con imanes","subfamiliaNorm":"Juguetes_con_imanes","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"50","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes de material rígido","subfamiliaNorm":"Juguetes_de_material_rígido","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"51","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes de material rígido","subfamiliaNorm":"Juguetes_de_material_rígido","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"52","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes de material rígido","subfamiliaNorm":"Juguetes_de_material_rígido","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"53","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes de material rígido","subfamiliaNorm":"Juguetes_de_material_rígido","literal":"6.1/6.1.5","ensayo":"Ensayo de Tracción","norma":"EN 71-1 / 8.4.2.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"54","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes de material rígido","subfamiliaNorm":"Juguetes_de_material_rígido","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"55","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes de material rígido","subfamiliaNorm":"Juguetes_de_material_rígido","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"56","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes de material rígido","subfamiliaNorm":"Juguetes_de_material_rígido","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"57","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes de material rígido","subfamiliaNorm":"Juguetes_de_material_rígido","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"58","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes y accesorios que dejan traza","subfamiliaNorm":"Juguetes_y_accesorios_que_dejan_traza","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"59","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes y accesorios que dejan traza","subfamiliaNorm":"Juguetes_y_accesorios_que_dejan_traza","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"60","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes y accesorios que dejan traza","subfamiliaNorm":"Juguetes_y_accesorios_que_dejan_traza","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"61","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Juguetes y accesorios que dejan traza","subfamiliaNorm":"Juguetes_y_accesorios_que_dejan_traza","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"62","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Lazos y cuerdas de saltar","subfamiliaNorm":"Lazos_y_cuerdas_de_saltar","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"63","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Lazos y cuerdas de saltar","subfamiliaNorm":"Lazos_y_cuerdas_de_saltar","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"64","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Lazos y cuerdas de saltar","subfamiliaNorm":"Lazos_y_cuerdas_de_saltar","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"65","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Lazos y cuerdas de saltar","subfamiliaNorm":"Lazos_y_cuerdas_de_saltar","literal":"6.1/6.1.5","ensayo":"Ensayo de Tracción","norma":"EN 71-1 / 8.4.2.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"66","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Lazos y cuerdas de saltar","subfamiliaNorm":"Lazos_y_cuerdas_de_saltar","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"67","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Lazos y cuerdas de saltar","subfamiliaNorm":"Lazos_y_cuerdas_de_saltar","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"68","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Lazos y cuerdas de saltar","subfamiliaNorm":"Lazos_y_cuerdas_de_saltar","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"69","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"70","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"71","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"72","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","literal":"6.1/6.1.2","ensayo":"Ensayo de tracción para elementos de protección","norma":"EN 71-1 / 8.4","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"73","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"74","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","literal":"6.1/6.1.5","ensayo":"Determinación de la energia cinética","norma":"EN 71-1 / 8.24","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"75","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","literal":"6.1/6.1.3","ensayo":"Ensayo de bolas pequeñas y de ventosas (si aplica)","norma":"EN 71-1/8.32","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"76","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"77","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"78","familia":"Juguetes de material rígido","familiaNorm":"Juguetes_de_material_rígido","subfamilia":"Proyectiles de juguete","subfamiliaNorm":"Proyectiles_de_juguete","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"79","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"80","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"81","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"82","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.1/6.1.5","ensayo":"Ensayo de Tracción","norma":"EN 71-1 / 8.4.2.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"83","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"84","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.1/6.1.4","ensayo":"Cilindro para partes pequeñas","norma":"EN 71-1 / 8.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"85","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.1/6.1.3","ensayo":"Cuerdas, cadenas y cables eléctricos de juguetes","norma":"EN 71-1 / 5.4","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"86","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"87","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.1/6.1.9","ensayo":"Determinación de los niveles de presión sonora de emisión.","norma":"EN 71-1 / 8.28","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"88","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.2","ensayo":"Determinación de nivel de F-talatos","norma":"N/A","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"89","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"90","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"91","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes con cables o cuerdas destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"92","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"93","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"94","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"95","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.1/6.1.5","ensayo":"Ensayo de Tracción","norma":"EN 71-1 / 8.4.2.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"96","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"97","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.1/6.1.9","ensayo":"Cilindro para partes pequeñas","norma":"EN 71-1 / 8.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"98","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.1/6.1.4","ensayo":"Forma geométrica de ciertos juguetes","norma":"EN 71-1 / 8.16","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"99","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"100","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.2","ensayo":"Determinación de los niveles de presión sonora de emisión.","norma":"EN 71-1 / 8.28","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"101","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.2","ensayo":"Determinación de nivel de F-talatos","norma":"N/A","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"102","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.1/6.1.3","ensayo":"Ensayo de figuras para jugar","norma":"EN 71-1 / 8.33","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"103","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"104","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"105","familia":"Juguetes destinados a menores de 36 meses","familiaNorm":"Juguetes_destinados_a_menores_de_36_meses","subfamilia":"Juguetes destinados a menores de 36 meses","subfamiliaNorm":"Juguetes_destinados_a_menores_de_36_meses","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"106","familia":"Juguetes Unidos a alimentos","familiaNorm":"Juguetes_Unidos_a_alimentos","subfamilia":"Juguetes Unidos a alimentos","subfamiliaNorm":"Juguetes_Unidos_a_alimentos","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"107","familia":"Juguetes Unidos a alimentos","familiaNorm":"Juguetes_Unidos_a_alimentos","subfamilia":"Juguetes Unidos a alimentos","subfamiliaNorm":"Juguetes_Unidos_a_alimentos","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"108","familia":"Juguetes Unidos a alimentos","familiaNorm":"Juguetes_Unidos_a_alimentos","subfamilia":"Juguetes Unidos a alimentos","subfamiliaNorm":"Juguetes_Unidos_a_alimentos","literal":"6.1/6.1.3","ensayo":"Cilindro para partes pequeñas","norma":"EN 71-1 / 8.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"109","familia":"Juguetes Unidos a alimentos","familiaNorm":"Juguetes_Unidos_a_alimentos","subfamilia":"Juguetes Unidos a alimentos","subfamiliaNorm":"Juguetes_Unidos_a_alimentos","literal":"6.1/6.1.1","ensayo":"Ensayo de impacto","norma":"EN 71-1 / 8.4.2.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"110","familia":"Juguetes Unidos a alimentos","familiaNorm":"Juguetes_Unidos_a_alimentos","subfamilia":"Juguetes Unidos a alimentos","subfamiliaNorm":"Juguetes_Unidos_a_alimentos","literal":"6.1/6.1.1","ensayo":"Ensayo de compresión","norma":"EN 71-1 / 8.8","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"111","familia":"Juguetes Unidos a alimentos","familiaNorm":"Juguetes_Unidos_a_alimentos","subfamilia":"Juguetes Unidos a alimentos","subfamiliaNorm":"Juguetes_Unidos_a_alimentos","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"112","familia":"Juguetes Unidos a alimentos","familiaNorm":"Juguetes_Unidos_a_alimentos","subfamilia":"Juguetes Unidos a alimentos","subfamiliaNorm":"Juguetes_Unidos_a_alimentos","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"113","familia":"Juguetes Unidos a alimentos","familiaNorm":"Juguetes_Unidos_a_alimentos","subfamilia":"Juguetes Unidos a alimentos","subfamiliaNorm":"Juguetes_Unidos_a_alimentos","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"114","familia":"Juguetes Unidos a alimentos","familiaNorm":"Juguetes_Unidos_a_alimentos","subfamilia":"Juguetes Unidos a alimentos","subfamiliaNorm":"Juguetes_Unidos_a_alimentos","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"115","familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","literal":"6.1/6.1.1","ensayo":"Resistencia estática","norma":"EN 71-1 / 8.21","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"116","familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","literal":"6.1/6.1.1","ensayo":"Resistencia dinámica","norma":"EN 71-1 / 8.22","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"117","familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"118","familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"119","familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","literal":"6.1/6.1.5","ensayo":"Transmisión y disposición de las ruedas","norma":"EN 71-1 / 4.15.1.6","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"120","familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","literal":"6.1/6.1.8","ensayo":"Funcionamiento de los frenos de la bicicleta de juguete","norma":"EN 71-1 / 8.26.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"121","familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"122","familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"123","familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"124","familia":"Montables","familiaNorm":"Montables","subfamilia":"Bicicletas con altura máxima de sillín de 435 mm","subfamiliaNorm":"Bicicletas_con_altura_máxima_de_sillín_de_435_mm","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"125","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.1/6.1.1","ensayo":"Resistencia estática","norma":"EN 71-1 / 8.21","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"126","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.1/6.1.1","ensayo":"Resistencia dinámica","norma":"EN 71-1 / 8.22","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"127","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"128","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"129","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.1/6.1.1","ensayo":"Estabilidad. Juguetes destinados a soportar el peso de un niño","norma":"EN 71-1 / 8.23.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"130","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"131","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"132","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.1/6.1.8","ensayo":"Funcionamiento de los frenos","norma":"EN 71-1 / 8.26","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"133","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"134","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"135","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montable no electricos","subfamiliaNorm":"Montable_no_electricos","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"136","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.1","ensayo":"Resistencia estática","norma":"EN 71-1 / 8.21","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"137","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.1","ensayo":"Resistencia dinámica","norma":"EN 71-1 / 8.22","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"138","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.1","ensayo":"Estabilidad. Juguetes destinados a soportar el peso de un niño","norma":"EN 71-1 / 8.23.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"139","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"140","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"141","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.2","ensayo":"Mecanismos de arrastre","norma":"EN 71-1 / 4.10.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"142","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"143","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.8","ensayo":"Funcionamiento del freno del motor. Ensayo horizontal.","norma":"EN 71-1/ 8.26.1.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"144","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.8","ensayo":"Determinación de la velocidad máxima de diseño de los juguetes eléctricos destinados a montarse en ellos","norma":"EN 71-1 / 8.29","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"145","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"146","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"147","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"148","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"149","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.1","ensayo":"Resistencia estática","norma":"EN 71-1 / 8.21","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"150","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.1","ensayo":"Resistencia dinámica","norma":"EN 71-1 / 8.22","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"151","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables eléctricos","subfamiliaNorm":"Montables_eléctricos","literal":"6.1/6.1.1","ensayo":"Estabilidad. Juguetes destinados a soportar el peso de un niño","norma":"EN 71-1 / 8.23.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"152","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables Estacionarios","subfamiliaNorm":"Montables_Estacionarios","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"153","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables Estacionarios","subfamiliaNorm":"Montables_Estacionarios","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"154","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables Estacionarios","subfamiliaNorm":"Montables_Estacionarios","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"155","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables Estacionarios","subfamiliaNorm":"Montables_Estacionarios","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"156","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables Estacionarios","subfamiliaNorm":"Montables_Estacionarios","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"157","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables Estacionarios","subfamiliaNorm":"Montables_Estacionarios","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"158","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables Estacionarios","subfamiliaNorm":"Montables_Estacionarios","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"159","familia":"Montables","familiaNorm":"Montables","subfamilia":"Montables Estacionarios","subfamiliaNorm":"Montables_Estacionarios","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"160","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.1/6.1.1","ensayo":"Resistencia estática","norma":"EN 71-1 / 8.21","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"161","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.1/6.1.1","ensayo":"Resistencia dinámica","norma":"EN 71-1 / 8.22","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"162","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"163","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"164","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.1/6.1.1","ensayo":"Resistencia de las barras de dirección de la patineta de juguete","norma":"EN 71-1 / 8.27","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"165","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"166","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.1/6.1.8","ensayo":"Funcionamiento de los frenos para patinetas de juguete","norma":"EN 71-1 / 8.26.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"167","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.1/6.1.8","ensayo":"Tamaño de la rueda de los scooters","norma":"EN 71-1/4.15.5.6","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"168","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"169","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"170","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"171","familia":"Montables","familiaNorm":"Montables","subfamilia":"Patinetas scooter","subfamiliaNorm":"Patinetas_scooter","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"172","familia":"Montables","familiaNorm":"Montables","subfamilia":"Sillitas de ruedas y cochecitos de muñecos","subfamiliaNorm":"Sillitas_de_ruedas_y_cochecitos_de_muñecos","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"173","familia":"Montables","familiaNorm":"Montables","subfamilia":"Sillitas de ruedas y cochecitos de muñecos","subfamiliaNorm":"Sillitas_de_ruedas_y_cochecitos_de_muñecos","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"174","familia":"Montables","familiaNorm":"Montables","subfamilia":"Sillitas de ruedas y cochecitos de muñecos","subfamiliaNorm":"Sillitas_de_ruedas_y_cochecitos_de_muñecos","literal":"6.1/6.1.5","ensayo":"Mecanismos plegables o deslizantes","norma":"EN 71-1 / 8.18.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"175","familia":"Montables","familiaNorm":"Montables","subfamilia":"Sillitas de ruedas y cochecitos de muñecos","subfamiliaNorm":"Sillitas_de_ruedas_y_cochecitos_de_muñecos","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"176","familia":"Montables","familiaNorm":"Montables","subfamilia":"Sillitas de ruedas y cochecitos de muñecos","subfamiliaNorm":"Sillitas_de_ruedas_y_cochecitos_de_muñecos","literal":"6.1/6.1.3","ensayo":"Cilindro para partes pequeñas","norma":"EN 71-1 / 8.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"177","familia":"Montables","familiaNorm":"Montables","subfamilia":"Sillitas de ruedas y cochecitos de muñecos","subfamiliaNorm":"Sillitas_de_ruedas_y_cochecitos_de_muñecos","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"178","familia":"Montables","familiaNorm":"Montables","subfamilia":"Sillitas de ruedas y cochecitos de muñecos","subfamiliaNorm":"Sillitas_de_ruedas_y_cochecitos_de_muñecos","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"179","familia":"Montables","familiaNorm":"Montables","subfamilia":"Sillitas de ruedas y cochecitos de muñecos","subfamiliaNorm":"Sillitas_de_ruedas_y_cochecitos_de_muñecos","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"180","familia":"Montables","familiaNorm":"Montables","subfamilia":"Sillitas de ruedas y cochecitos de muñecos","subfamiliaNorm":"Sillitas_de_ruedas_y_cochecitos_de_muñecos","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"181","familia":"Muñecos Plásticos Vestidos","familiaNorm":"Muñecos_Plásticos_Vestidos","subfamilia":"Muñecos Plásticos Vestidos","subfamiliaNorm":"Muñecos_Plásticos_Vestidos","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"182","familia":"Muñecos Plásticos Vestidos","familiaNorm":"Muñecos_Plásticos_Vestidos","subfamilia":"Muñecos Plásticos Vestidos","subfamiliaNorm":"Muñecos_Plásticos_Vestidos","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"183","familia":"Muñecos Plásticos Vestidos","familiaNorm":"Muñecos_Plásticos_Vestidos","subfamilia":"Muñecos Plásticos Vestidos","subfamiliaNorm":"Muñecos_Plásticos_Vestidos","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"184","familia":"Muñecos Plásticos Vestidos","familiaNorm":"Muñecos_Plásticos_Vestidos","subfamilia":"Muñecos Plásticos Vestidos","subfamiliaNorm":"Muñecos_Plásticos_Vestidos","literal":"6.1/6.1.5","ensayo":"Ensayo de Tracción","norma":"EN 71-1 / 8.4.2.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"185","familia":"Muñecos Plásticos Vestidos","familiaNorm":"Muñecos_Plásticos_Vestidos","subfamilia":"Muñecos Plásticos Vestidos","subfamiliaNorm":"Muñecos_Plásticos_Vestidos","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"186","familia":"Muñecos Plásticos Vestidos","familiaNorm":"Muñecos_Plásticos_Vestidos","subfamilia":"Muñecos Plásticos Vestidos","subfamiliaNorm":"Muñecos_Plásticos_Vestidos","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"187","familia":"Muñecos Plásticos Vestidos","familiaNorm":"Muñecos_Plásticos_Vestidos","subfamilia":"Muñecos Plásticos Vestidos","subfamiliaNorm":"Muñecos_Plásticos_Vestidos","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.5","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"188","familia":"Muñecos Plásticos Vestidos","familiaNorm":"Muñecos_Plásticos_Vestidos","subfamilia":"Muñecos Plásticos Vestidos","subfamiliaNorm":"Muñecos_Plásticos_Vestidos","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"189","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"190","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"191","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"192","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.5","ensayo":"Ensayo de Tracción costuras y materiales","norma":"EN 71-1 / 8.4.2.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"193","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.3","ensayo":"Cilindro para partes pequeñas","norma":"EN 71-1 / 8.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"194","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"195","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"196","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"197","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.5","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"198","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"199","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"200","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"201","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"202","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"/","ensayo":"Ensayo de Tracción costuras y materiales","norma":"EN 71-1 / 8.4.2.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"203","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Peluches, Muñecos Rellenos y juguetes flexibles","subfamiliaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","literal":"6.1/6.1.3","ensayo":"Cilindro para partes pequeñas","norma":"EN 71-1 / 8.2","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"204","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Juguetes de material flexible","subfamiliaNorm":"Juguetes_de_material_flexible","literal":"6.1/6.1.2","ensayo":"Accesibilidad de una parte o de un elemento","norma":"EN 71-1 / 8.10","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"205","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Juguetes de material flexible","subfamiliaNorm":"Juguetes_de_material_flexible","literal":"6.5","ensayo":"Medición del nivel de tensión con instrumento Calibrado","norma":"Resolución 0686:2018","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"206","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Juguetes de material flexible","subfamiliaNorm":"Juguetes_de_material_flexible","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"207","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Juguetes de material flexible","subfamiliaNorm":"Juguetes_de_material_flexible","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.5","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"208","familia":"Peluches, Muñecos Rellenos y juguetes flexibles","familiaNorm":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles","subfamilia":"Juguetes de material flexible","subfamiliaNorm":"Juguetes_de_material_flexible","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"209","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Globos de Latex","subfamiliaNorm":"Globos_de_Latex","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"210","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Globos de Latex","subfamiliaNorm":"Globos_de_Latex","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"211","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Globos de Latex","subfamiliaNorm":"Globos_de_Latex","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":true,"VIG_2":true,"RENOVACION":true},{"filaFuente":"212","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Globos plásticos","subfamiliaNorm":"Globos_plásticos","literal":"6.1/6.1.3","ensayo":"Espesor, láminas de plástico","norma":"EN 71-1 / 8.25.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"213","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Globos plásticos","subfamiliaNorm":"Globos_plásticos","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"214","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Globos plásticos","subfamiliaNorm":"Globos_plásticos","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"215","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Globos plásticos","subfamiliaNorm":"Globos_plásticos","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"216","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Manillas, gafas que alumbran","subfamiliaNorm":"Manillas_gafas_que_alumbran","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"217","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Manillas, gafas que alumbran","subfamiliaNorm":"Manillas_gafas_que_alumbran","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"218","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Manillas, gafas que alumbran","subfamiliaNorm":"Manillas_gafas_que_alumbran","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"219","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Manillas, gafas que alumbran","subfamiliaNorm":"Manillas_gafas_que_alumbran","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"220","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Papel y cartón","subfamiliaNorm":"Papel_y_cartón","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"221","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Papel y cartón","subfamiliaNorm":"Papel_y_cartón","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"222","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Papel y cartón","subfamiliaNorm":"Papel_y_cartón","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"223","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Pelotas pequeñas y canicas","subfamiliaNorm":"Pelotas_pequeñas_y_canicas","literal":"6.1/6.1.3","ensayo":"Ensayo de bolas pequeñas y de ventosas","norma":"EN 71-1 / 8.32","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"224","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Pelotas pequeñas y canicas","subfamiliaNorm":"Pelotas_pequeñas_y_canicas","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"225","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Pelotas pequeñas y canicas","subfamiliaNorm":"Pelotas_pequeñas_y_canicas","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"226","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Pelotas pequeñas y canicas","subfamiliaNorm":"Pelotas_pequeñas_y_canicas","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"227","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Piñateria plastica","subfamiliaNorm":"Piñateria_plastica","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"228","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Piñateria plastica","subfamiliaNorm":"Piñateria_plastica","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"229","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Piñateria plastica","subfamiliaNorm":"Piñateria_plastica","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"230","familia":"Piñatería","familiaNorm":"Piñatería","subfamilia":"Piñateria plastica","subfamiliaNorm":"Piñateria_plastica","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"231","familia":"Juguetes que contienen liquido o gel","familiaNorm":"Juguetes_que_contienen_liquido_o_gel","subfamilia":"Juguetes que contienen liquido o gel","subfamiliaNorm":"Juguetes_que_contienen_liquido_o_gel","literal":"6.1/6.1.9","ensayo":"Estanquiedad de los juguetes que contienen líquido","norma":"EN 71-1/8.15","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"232","familia":"Juguetes que contienen liquido o gel","familiaNorm":"Juguetes_que_contienen_liquido_o_gel","subfamilia":"Juguetes que contienen liquido o gel","subfamiliaNorm":"Juguetes_que_contienen_liquido_o_gel","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"233","familia":"Juguetes que contienen liquido o gel","familiaNorm":"Juguetes_que_contienen_liquido_o_gel","subfamilia":"Juguetes que contienen liquido o gel","subfamiliaNorm":"Juguetes_que_contienen_liquido_o_gel","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"234","familia":"Juguetes que contienen liquido o gel","familiaNorm":"Juguetes_que_contienen_liquido_o_gel","subfamilia":"Juguetes que contienen liquido o gel","subfamiliaNorm":"Juguetes_que_contienen_liquido_o_gel","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":true,"VIG_2":true,"RENOVACION":true},{"filaFuente":"235","familia":"Juegos de experimentos químicos y actividades relacionadas","familiaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","subfamilia":"Juegos de experimentos químicos y actividades relacionadas","subfamiliaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","literal":"6.1/6.1.10","ensayo":"Juegos de experimentos químicos y actividades relacionadas","norma":"EN 71-4","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"236","familia":"Juegos de experimentos químicos y actividades relacionadas","familiaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","subfamilia":"Juegos de experimentos químicos y actividades relacionadas","subfamiliaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"237","familia":"Juegos de experimentos químicos y actividades relacionadas","familiaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","subfamilia":"Juegos de experimentos químicos y actividades relacionadas","subfamiliaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"238","familia":"Juegos de experimentos químicos y actividades relacionadas","familiaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","subfamilia":"Juegos de experimentos químicos y actividades relacionadas","subfamiliaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"239","familia":"Juegos de experimentos químicos y actividades relacionadas","familiaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","subfamilia":"Juegos de experimentos químicos y actividades relacionadas","subfamiliaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","literal":"6.1/6.1.5","ensayo":"Ensayo de Tracción","norma":"EN 71-1 / 8.4.2.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"240","familia":"Juegos de experimentos químicos y actividades relacionadas","familiaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","subfamilia":"Juegos de experimentos químicos y actividades relacionadas","subfamiliaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"241","familia":"Juegos de experimentos químicos y actividades relacionadas","familiaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","subfamilia":"Juegos de experimentos químicos y actividades relacionadas","subfamiliaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"242","familia":"Juegos de experimentos químicos y actividades relacionadas","familiaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","subfamilia":"Juegos de experimentos químicos y actividades relacionadas","subfamiliaNorm":"Juegos_de_experimentos_químicos_y_actividades_relacionadas","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true},{"filaFuente":"243","familia":"Juegos químicos distintos de los juegos de experimento","familiaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","subfamilia":"Juegos químicos distintos de los juegos de experimento","subfamiliaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","literal":"6.1/6.1.10","ensayo":"Juegos químicos distintos de los juegos de experimento","norma":"EN 71-5","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"244","familia":"Juegos químicos distintos de los juegos de experimento","familiaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","subfamilia":"Juegos químicos distintos de los juegos de experimento","subfamiliaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","literal":"6.1/6.1.2","ensayo":"Acuidad de los Bordes","norma":"EN 71-1 / 8.11","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"245","familia":"Juegos químicos distintos de los juegos de experimento","familiaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","subfamilia":"Juegos químicos distintos de los juegos de experimento","subfamiliaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","literal":"6.1/6.1.2","ensayo":"Acuidad de las Puntas","norma":"EN 71-1 / 8.12","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"246","familia":"Juegos químicos distintos de los juegos de experimento","familiaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","subfamilia":"Juegos químicos distintos de los juegos de experimento","subfamiliaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","literal":"6.1/6.1.5","ensayo":"Ensayo de Torque","norma":"EN 71-1 / 8.3","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"247","familia":"Juegos químicos distintos de los juegos de experimento","familiaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","subfamilia":"Juegos químicos distintos de los juegos de experimento","subfamiliaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","literal":"6.1/6.1.5","ensayo":"Ensayo de Tracción","norma":"EN 71-1 / 8.4.2.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":false},{"filaFuente":"248","familia":"Juegos químicos distintos de los juegos de experimento","familiaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","subfamilia":"Juegos químicos distintos de los juegos de experimento","subfamiliaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","literal":"6.1/6.1.9","ensayo":"Limpieza de Materiales","norma":"EN 71-1/4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"249","familia":"Juegos químicos distintos de los juegos de experimento","familiaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","subfamilia":"Juegos químicos distintos de los juegos de experimento","subfamiliaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","literal":"6.1/6.3","ensayo":"Inflamabilidad","norma":"EN 71-2 / 4.1","OT":true,"VIG_1":true,"VIG_2":false,"RENOVACION":true},{"filaFuente":"250","familia":"Juegos químicos distintos de los juegos de experimento","familiaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","subfamilia":"Juegos químicos distintos de los juegos de experimento","subfamiliaNorm":"Juegos_químicos_distintos_de_los_juegos_de_experimento","literal":"6.4","ensayo":"Migración de ciertos elementos","norma":"EN 71-3","OT":true,"VIG_1":false,"VIG_2":true,"RENOVACION":true}],"combinations":[{"idClasificacion":"C01","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C01-S001","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_con_imanes + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_con_imanes","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":16,"OT":16,"VIG_1":15,"VIG_2":1,"RENOVACION":12}},{"idClasificacion":"C01","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C01-S002","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_con_imanes + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_con_imanes","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":17,"OT":17,"VIG_1":16,"VIG_2":1,"RENOVACION":12}},{"idClasificacion":"C01","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C01-S003","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C01","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C01-S004","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C01","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C01-S005","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_y_accesorios_que_dejan_traza + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_y_accesorios_que_dejan_traza","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C01","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C01-S006","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_y_accesorios_que_dejan_traza + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_y_accesorios_que_dejan_traza","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C01","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C01-S007","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Lazos_y_cuerdas_de_saltar + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Lazos_y_cuerdas_de_saltar","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C01","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C01-S008","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Lazos_y_cuerdas_de_saltar + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Lazos_y_cuerdas_de_saltar","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C01","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C01-S009","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Proyectiles_de_juguete + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Proyectiles_de_juguete","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":17,"OT":17,"VIG_1":16,"VIG_2":1,"RENOVACION":13}},{"idClasificacion":"C01","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C01-S010","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Proyectiles_de_juguete + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Proyectiles_de_juguete","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":18,"OT":18,"VIG_1":17,"VIG_2":1,"RENOVACION":13}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S001","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Bicicletas_con_altura_máxima_de_sillín_de_435_mm + Juguetes_con_imanes","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Bicicletas_con_altura_máxima_de_sillín_de_435_mm","Juguetes_con_imanes"],"counts":{"total":17,"OT":17,"VIG_1":16,"VIG_2":1,"RENOVACION":13}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S002","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Bicicletas_con_altura_máxima_de_sillín_de_435_mm + Juguetes_de_material_rígido","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Bicicletas_con_altura_máxima_de_sillín_de_435_mm","Juguetes_de_material_rígido"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S003","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Bicicletas_con_altura_máxima_de_sillín_de_435_mm + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Bicicletas_con_altura_máxima_de_sillín_de_435_mm","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S004","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Bicicletas_con_altura_máxima_de_sillín_de_435_mm + Lazos_y_cuerdas_de_saltar","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Bicicletas_con_altura_máxima_de_sillín_de_435_mm","Lazos_y_cuerdas_de_saltar"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S005","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Bicicletas_con_altura_máxima_de_sillín_de_435_mm + Proyectiles_de_juguete","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Bicicletas_con_altura_máxima_de_sillín_de_435_mm","Proyectiles_de_juguete"],"counts":{"total":17,"OT":17,"VIG_1":16,"VIG_2":1,"RENOVACION":13}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S006","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montable_no_electricos + Juguetes_con_imanes","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montable_no_electricos","Juguetes_con_imanes"],"counts":{"total":17,"OT":17,"VIG_1":16,"VIG_2":1,"RENOVACION":13}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S007","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montable_no_electricos + Juguetes_de_material_rígido","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montable_no_electricos","Juguetes_de_material_rígido"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S008","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montable_no_electricos + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montable_no_electricos","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S009","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montable_no_electricos + Lazos_y_cuerdas_de_saltar","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montable_no_electricos","Lazos_y_cuerdas_de_saltar"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S010","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montable_no_electricos + Proyectiles_de_juguete","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montable_no_electricos","Proyectiles_de_juguete"],"counts":{"total":17,"OT":17,"VIG_1":16,"VIG_2":1,"RENOVACION":13}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S011","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_eléctricos + Juguetes_con_imanes","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_eléctricos","Juguetes_con_imanes"],"counts":{"total":18,"OT":18,"VIG_1":17,"VIG_2":1,"RENOVACION":14}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S012","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_eléctricos + Juguetes_de_material_rígido","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_eléctricos","Juguetes_de_material_rígido"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":12}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S013","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_eléctricos + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_eléctricos","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":12}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S014","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_eléctricos + Lazos_y_cuerdas_de_saltar","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_eléctricos","Lazos_y_cuerdas_de_saltar"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":12}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S015","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_eléctricos + Proyectiles_de_juguete","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_eléctricos","Proyectiles_de_juguete"],"counts":{"total":18,"OT":18,"VIG_1":17,"VIG_2":1,"RENOVACION":14}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S016","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_Estacionarios + Juguetes_con_imanes","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_Estacionarios","Juguetes_con_imanes"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S017","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_Estacionarios + Juguetes_de_material_rígido","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_Estacionarios","Juguetes_de_material_rígido"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S018","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_Estacionarios + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_Estacionarios","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S019","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_Estacionarios + Lazos_y_cuerdas_de_saltar","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_Estacionarios","Lazos_y_cuerdas_de_saltar"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S020","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_Estacionarios + Proyectiles_de_juguete","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_Estacionarios","Proyectiles_de_juguete"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S021","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Patinetas_scooter + Juguetes_con_imanes","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Patinetas_scooter","Juguetes_con_imanes"],"counts":{"total":18,"OT":18,"VIG_1":17,"VIG_2":1,"RENOVACION":14}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S022","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Patinetas_scooter + Juguetes_de_material_rígido","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Patinetas_scooter","Juguetes_de_material_rígido"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":12}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S023","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Patinetas_scooter + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Patinetas_scooter","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":12}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S024","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Patinetas_scooter + Lazos_y_cuerdas_de_saltar","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Patinetas_scooter","Lazos_y_cuerdas_de_saltar"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":12}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S025","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Patinetas_scooter + Proyectiles_de_juguete","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Patinetas_scooter","Proyectiles_de_juguete"],"counts":{"total":18,"OT":18,"VIG_1":17,"VIG_2":1,"RENOVACION":14}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S026","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Sillitas_de_ruedas_y_cochecitos_de_muñecos + Juguetes_con_imanes","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Sillitas_de_ruedas_y_cochecitos_de_muñecos","Juguetes_con_imanes"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S027","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Sillitas_de_ruedas_y_cochecitos_de_muñecos + Juguetes_de_material_rígido","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Sillitas_de_ruedas_y_cochecitos_de_muñecos","Juguetes_de_material_rígido"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S028","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Sillitas_de_ruedas_y_cochecitos_de_muñecos + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Sillitas_de_ruedas_y_cochecitos_de_muñecos","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S029","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Sillitas_de_ruedas_y_cochecitos_de_muñecos + Lazos_y_cuerdas_de_saltar","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Sillitas_de_ruedas_y_cochecitos_de_muñecos","Lazos_y_cuerdas_de_saltar"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C02","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_de_material_rígido","idSubcombinacion":"C02-S030","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Sillitas_de_ruedas_y_cochecitos_de_muñecos + Proyectiles_de_juguete","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Sillitas_de_ruedas_y_cochecitos_de_muñecos","Proyectiles_de_juguete"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S001","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Bicicletas_con_altura_máxima_de_sillín_de_435_mm + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Bicicletas_con_altura_máxima_de_sillín_de_435_mm","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":18,"OT":18,"VIG_1":17,"VIG_2":1,"RENOVACION":15}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S002","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Bicicletas_con_altura_máxima_de_sillín_de_435_mm + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Bicicletas_con_altura_máxima_de_sillín_de_435_mm","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":19,"OT":19,"VIG_1":18,"VIG_2":1,"RENOVACION":15}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S003","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montable_no_electricos + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montable_no_electricos","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":18,"OT":18,"VIG_1":17,"VIG_2":1,"RENOVACION":15}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S004","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montable_no_electricos + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montable_no_electricos","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":19,"OT":19,"VIG_1":18,"VIG_2":1,"RENOVACION":15}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S005","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_eléctricos + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_eléctricos","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":19,"OT":19,"VIG_1":18,"VIG_2":1,"RENOVACION":16}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S006","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_eléctricos + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_eléctricos","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":20,"OT":20,"VIG_1":19,"VIG_2":1,"RENOVACION":16}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S007","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_Estacionarios + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_Estacionarios","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S008","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Montables_Estacionarios + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Montables_Estacionarios","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S009","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Patinetas_scooter + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Patinetas_scooter","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":19,"OT":19,"VIG_1":18,"VIG_2":1,"RENOVACION":16}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S010","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Patinetas_scooter + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Patinetas_scooter","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":20,"OT":20,"VIG_1":19,"VIG_2":1,"RENOVACION":16}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S011","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Sillitas_de_ruedas_y_cochecitos_de_muñecos + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Sillitas_de_ruedas_y_cochecitos_de_muñecos","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":13}},{"idClasificacion":"C03","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Montables + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C03-S012","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Sillitas_de_ruedas_y_cochecitos_de_muñecos + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Carros_de_juguete_de_cualquier_material","Montables","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Sillitas_de_ruedas_y_cochecitos_de_muñecos","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":16,"OT":16,"VIG_1":15,"VIG_2":1,"RENOVACION":13}},{"idClasificacion":"C04","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","idSubcombinacion":"C04-S001","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_que_contienen_liquido_o_gel + Juguetes_con_imanes","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_que_contienen_liquido_o_gel","Juguetes_con_imanes"],"counts":{"total":14,"OT":14,"VIG_1":14,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C04","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","idSubcombinacion":"C04-S002","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"counts":{"total":11,"OT":11,"VIG_1":11,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C04","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","idSubcombinacion":"C04-S003","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_que_contienen_liquido_o_gel + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_que_contienen_liquido_o_gel","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":11,"OT":11,"VIG_1":11,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C04","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","idSubcombinacion":"C04-S004","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_que_contienen_liquido_o_gel + Lazos_y_cuerdas_de_saltar","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_que_contienen_liquido_o_gel","Lazos_y_cuerdas_de_saltar"],"counts":{"total":11,"OT":11,"VIG_1":11,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C04","combinacionClasificacion":"Carros_de_juguete_de_cualquier_material + Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","idSubcombinacion":"C04-S005","combinacionSubfamilias":"Carros_de_juguete_de_cualquier_material + Juguetes_que_contienen_liquido_o_gel + Proyectiles_de_juguete","classFamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"subfamilies":["Carros_de_juguete_de_cualquier_material","Juguetes_que_contienen_liquido_o_gel","Proyectiles_de_juguete"],"counts":{"total":14,"OT":14,"VIG_1":14,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C05","combinacionClasificacion":"Cometas_y_juguetes_voladores + Juguetes_de_material_rígido","idSubcombinacion":"C05-S001","combinacionSubfamilias":"Cometas_y_juguetes_voladores + Juguetes_con_imanes","classFamilies":["Cometas_y_juguetes_voladores","Juguetes_de_material_rígido"],"subfamilies":["Cometas_y_juguetes_voladores","Juguetes_con_imanes"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C05","combinacionClasificacion":"Cometas_y_juguetes_voladores + Juguetes_de_material_rígido","idSubcombinacion":"C05-S002","combinacionSubfamilias":"Cometas_y_juguetes_voladores + Juguetes_de_material_rígido","classFamilies":["Cometas_y_juguetes_voladores","Juguetes_de_material_rígido"],"subfamilies":["Cometas_y_juguetes_voladores","Juguetes_de_material_rígido"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C05","combinacionClasificacion":"Cometas_y_juguetes_voladores + Juguetes_de_material_rígido","idSubcombinacion":"C05-S003","combinacionSubfamilias":"Cometas_y_juguetes_voladores + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Cometas_y_juguetes_voladores","Juguetes_de_material_rígido"],"subfamilies":["Cometas_y_juguetes_voladores","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":7,"OT":7,"VIG_1":6,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C05","combinacionClasificacion":"Cometas_y_juguetes_voladores + Juguetes_de_material_rígido","idSubcombinacion":"C05-S004","combinacionSubfamilias":"Cometas_y_juguetes_voladores + Lazos_y_cuerdas_de_saltar","classFamilies":["Cometas_y_juguetes_voladores","Juguetes_de_material_rígido"],"subfamilies":["Cometas_y_juguetes_voladores","Lazos_y_cuerdas_de_saltar"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C05","combinacionClasificacion":"Cometas_y_juguetes_voladores + Juguetes_de_material_rígido","idSubcombinacion":"C05-S005","combinacionSubfamilias":"Cometas_y_juguetes_voladores + Proyectiles_de_juguete","classFamilies":["Cometas_y_juguetes_voladores","Juguetes_de_material_rígido"],"subfamilies":["Cometas_y_juguetes_voladores","Proyectiles_de_juguete"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S001","combinacionSubfamilias":"Barbas_bigotes_o_pelucas + Juguetes_con_imanes","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Barbas_bigotes_o_pelucas","Juguetes_con_imanes"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S002","combinacionSubfamilias":"Barbas_bigotes_o_pelucas + Juguetes_de_material_rígido","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Barbas_bigotes_o_pelucas","Juguetes_de_material_rígido"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S003","combinacionSubfamilias":"Barbas_bigotes_o_pelucas + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Barbas_bigotes_o_pelucas","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":4,"OT":4,"VIG_1":3,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S004","combinacionSubfamilias":"Barbas_bigotes_o_pelucas + Lazos_y_cuerdas_de_saltar","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Barbas_bigotes_o_pelucas","Lazos_y_cuerdas_de_saltar"],"counts":{"total":7,"OT":7,"VIG_1":6,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S005","combinacionSubfamilias":"Barbas_bigotes_o_pelucas + Proyectiles_de_juguete","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Barbas_bigotes_o_pelucas","Proyectiles_de_juguete"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S006","combinacionSubfamilias":"Disfraces_y_accesorios_generales + Juguetes_con_imanes","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Disfraces_y_accesorios_generales","Juguetes_con_imanes"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S007","combinacionSubfamilias":"Disfraces_y_accesorios_generales + Juguetes_de_material_rígido","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Disfraces_y_accesorios_generales","Juguetes_de_material_rígido"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S008","combinacionSubfamilias":"Disfraces_y_accesorios_generales + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Disfraces_y_accesorios_generales","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":4,"OT":4,"VIG_1":3,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S009","combinacionSubfamilias":"Disfraces_y_accesorios_generales + Lazos_y_cuerdas_de_saltar","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Disfraces_y_accesorios_generales","Lazos_y_cuerdas_de_saltar"],"counts":{"total":7,"OT":7,"VIG_1":6,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S010","combinacionSubfamilias":"Disfraces_y_accesorios_generales + Proyectiles_de_juguete","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Disfraces_y_accesorios_generales","Proyectiles_de_juguete"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S011","combinacionSubfamilias":"Mascaras_y_Cascos + Juguetes_con_imanes","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Mascaras_y_Cascos","Juguetes_con_imanes"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S012","combinacionSubfamilias":"Mascaras_y_Cascos + Juguetes_de_material_rígido","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Mascaras_y_Cascos","Juguetes_de_material_rígido"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S013","combinacionSubfamilias":"Mascaras_y_Cascos + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Mascaras_y_Cascos","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":4,"OT":4,"VIG_1":3,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S014","combinacionSubfamilias":"Mascaras_y_Cascos + Lazos_y_cuerdas_de_saltar","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Mascaras_y_Cascos","Lazos_y_cuerdas_de_saltar"],"counts":{"total":7,"OT":7,"VIG_1":6,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C06","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_de_material_rígido","idSubcombinacion":"C06-S015","combinacionSubfamilias":"Mascaras_y_Cascos + Proyectiles_de_juguete","classFamilies":["Disfraces_y_accesorios","Juguetes_de_material_rígido"],"subfamilies":["Mascaras_y_Cascos","Proyectiles_de_juguete"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C07","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C07-S001","combinacionSubfamilias":"Barbas_bigotes_o_pelucas + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Disfraces_y_accesorios","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Barbas_bigotes_o_pelucas","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C07","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C07-S002","combinacionSubfamilias":"Barbas_bigotes_o_pelucas + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Disfraces_y_accesorios","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Barbas_bigotes_o_pelucas","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C07","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C07-S003","combinacionSubfamilias":"Disfraces_y_accesorios_generales + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Disfraces_y_accesorios","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Disfraces_y_accesorios_generales","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C07","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C07-S004","combinacionSubfamilias":"Disfraces_y_accesorios_generales + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Disfraces_y_accesorios","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Disfraces_y_accesorios_generales","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C07","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C07-S005","combinacionSubfamilias":"Mascaras_y_Cascos + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Disfraces_y_accesorios","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Mascaras_y_Cascos","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C07","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C07-S006","combinacionSubfamilias":"Mascaras_y_Cascos + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Disfraces_y_accesorios","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Mascaras_y_Cascos","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C08","combinacionClasificacion":"Disfraces_y_accesorios + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C08-S001","combinacionSubfamilias":"Barbas_bigotes_o_pelucas + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","classFamilies":["Disfraces_y_accesorios","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Barbas_bigotes_o_pelucas","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C08","combinacionClasificacion":"Disfraces_y_accesorios + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C08-S002","combinacionSubfamilias":"Barbas_bigotes_o_pelucas + Juguetes_de_material_flexible","classFamilies":["Disfraces_y_accesorios","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Barbas_bigotes_o_pelucas","Juguetes_de_material_flexible"],"counts":{"total":5,"OT":5,"VIG_1":4,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C08","combinacionClasificacion":"Disfraces_y_accesorios + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C08-S003","combinacionSubfamilias":"Disfraces_y_accesorios_generales + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","classFamilies":["Disfraces_y_accesorios","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Disfraces_y_accesorios_generales","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C08","combinacionClasificacion":"Disfraces_y_accesorios + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C08-S004","combinacionSubfamilias":"Disfraces_y_accesorios_generales + Juguetes_de_material_flexible","classFamilies":["Disfraces_y_accesorios","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Disfraces_y_accesorios_generales","Juguetes_de_material_flexible"],"counts":{"total":6,"OT":6,"VIG_1":5,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C08","combinacionClasificacion":"Disfraces_y_accesorios + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C08-S005","combinacionSubfamilias":"Mascaras_y_Cascos + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","classFamilies":["Disfraces_y_accesorios","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Mascaras_y_Cascos","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C08","combinacionClasificacion":"Disfraces_y_accesorios + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C08-S006","combinacionSubfamilias":"Mascaras_y_Cascos + Juguetes_de_material_flexible","classFamilies":["Disfraces_y_accesorios","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Mascaras_y_Cascos","Juguetes_de_material_flexible"],"counts":{"total":6,"OT":6,"VIG_1":5,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C09","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C09-S001","combinacionSubfamilias":"Barbas_bigotes_o_pelucas + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Disfraces_y_accesorios","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Barbas_bigotes_o_pelucas","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":4,"OT":4,"VIG_1":4,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C09","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C09-S002","combinacionSubfamilias":"Disfraces_y_accesorios_generales + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Disfraces_y_accesorios","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Disfraces_y_accesorios_generales","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":5,"OT":5,"VIG_1":5,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C09","combinacionClasificacion":"Disfraces_y_accesorios + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C09-S003","combinacionSubfamilias":"Mascaras_y_Cascos + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Disfraces_y_accesorios","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Mascaras_y_Cascos","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":5,"OT":5,"VIG_1":5,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C10","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C10-S001","combinacionSubfamilias":"Carpas + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carpas","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C10","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C10-S002","combinacionSubfamilias":"Carpas + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Carpas","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C10","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C10-S003","combinacionSubfamilias":"Inflables_para_piscina + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Inflables_para_piscina","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C10","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C10-S004","combinacionSubfamilias":"Inflables_para_piscina + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Inflables_para_piscina","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C11","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_de_material_rígido","idSubcombinacion":"C11-S001","combinacionSubfamilias":"Carpas + Juguetes_con_imanes","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_de_material_rígido"],"subfamilies":["Carpas","Juguetes_con_imanes"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C11","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_de_material_rígido","idSubcombinacion":"C11-S002","combinacionSubfamilias":"Carpas + Juguetes_de_material_rígido","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_de_material_rígido"],"subfamilies":["Carpas","Juguetes_de_material_rígido"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C11","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_de_material_rígido","idSubcombinacion":"C11-S003","combinacionSubfamilias":"Carpas + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_de_material_rígido"],"subfamilies":["Carpas","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":5,"OT":5,"VIG_1":4,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C11","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_de_material_rígido","idSubcombinacion":"C11-S004","combinacionSubfamilias":"Carpas + Lazos_y_cuerdas_de_saltar","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_de_material_rígido"],"subfamilies":["Carpas","Lazos_y_cuerdas_de_saltar"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C11","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_de_material_rígido","idSubcombinacion":"C11-S005","combinacionSubfamilias":"Carpas + Proyectiles_de_juguete","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_de_material_rígido"],"subfamilies":["Carpas","Proyectiles_de_juguete"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C11","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_de_material_rígido","idSubcombinacion":"C11-S006","combinacionSubfamilias":"Inflables_para_piscina + Juguetes_con_imanes","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_de_material_rígido"],"subfamilies":["Inflables_para_piscina","Juguetes_con_imanes"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C11","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_de_material_rígido","idSubcombinacion":"C11-S007","combinacionSubfamilias":"Inflables_para_piscina + Juguetes_de_material_rígido","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_de_material_rígido"],"subfamilies":["Inflables_para_piscina","Juguetes_de_material_rígido"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C11","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_de_material_rígido","idSubcombinacion":"C11-S008","combinacionSubfamilias":"Inflables_para_piscina + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_de_material_rígido"],"subfamilies":["Inflables_para_piscina","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":5,"OT":5,"VIG_1":4,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C11","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_de_material_rígido","idSubcombinacion":"C11-S009","combinacionSubfamilias":"Inflables_para_piscina + Lazos_y_cuerdas_de_saltar","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_de_material_rígido"],"subfamilies":["Inflables_para_piscina","Lazos_y_cuerdas_de_saltar"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C11","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_de_material_rígido","idSubcombinacion":"C11-S010","combinacionSubfamilias":"Inflables_para_piscina + Proyectiles_de_juguete","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_de_material_rígido"],"subfamilies":["Inflables_para_piscina","Proyectiles_de_juguete"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C12","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C12-S001","combinacionSubfamilias":"Carpas + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Carpas","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":6,"OT":6,"VIG_1":6,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C12","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C12-S002","combinacionSubfamilias":"Inflables_para_piscina + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Inflables_para_piscina","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":5,"OT":5,"VIG_1":5,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S001","combinacionSubfamilias":"Carpas + Bicicletas_con_altura_máxima_de_sillín_de_435_mm","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Carpas","Bicicletas_con_altura_máxima_de_sillín_de_435_mm"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S002","combinacionSubfamilias":"Carpas + Montable_no_electricos","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Carpas","Montable_no_electricos"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S003","combinacionSubfamilias":"Carpas + Montables_eléctricos","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Carpas","Montables_eléctricos"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S004","combinacionSubfamilias":"Carpas + Montables_Estacionarios","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Carpas","Montables_Estacionarios"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S005","combinacionSubfamilias":"Carpas + Patinetas_scooter","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Carpas","Patinetas_scooter"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S006","combinacionSubfamilias":"Carpas + Sillitas_de_ruedas_y_cochecitos_de_muñecos","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Carpas","Sillitas_de_ruedas_y_cochecitos_de_muñecos"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S007","combinacionSubfamilias":"Inflables_para_piscina + Bicicletas_con_altura_máxima_de_sillín_de_435_mm","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Inflables_para_piscina","Bicicletas_con_altura_máxima_de_sillín_de_435_mm"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S008","combinacionSubfamilias":"Inflables_para_piscina + Montable_no_electricos","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Inflables_para_piscina","Montable_no_electricos"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S009","combinacionSubfamilias":"Inflables_para_piscina + Montables_eléctricos","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Inflables_para_piscina","Montables_eléctricos"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S010","combinacionSubfamilias":"Inflables_para_piscina + Montables_Estacionarios","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Inflables_para_piscina","Montables_Estacionarios"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S011","combinacionSubfamilias":"Inflables_para_piscina + Patinetas_scooter","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Inflables_para_piscina","Patinetas_scooter"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C13","combinacionClasificacion":"Inflables_o_que_pueden_alojar_internamente + Montables","idSubcombinacion":"C13-S012","combinacionSubfamilias":"Inflables_para_piscina + Sillitas_de_ruedas_y_cochecitos_de_muñecos","classFamilies":["Inflables_o_que_pueden_alojar_internamente","Montables"],"subfamilies":["Inflables_para_piscina","Sillitas_de_ruedas_y_cochecitos_de_muñecos"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C14","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C14-S001","combinacionSubfamilias":"Juguetes_con_imanes + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Juguetes_con_imanes","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C14","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C14-S002","combinacionSubfamilias":"Juguetes_con_imanes + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Juguetes_con_imanes","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":16,"OT":16,"VIG_1":15,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C14","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C14-S003","combinacionSubfamilias":"Juguetes_de_material_rígido + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Juguetes_de_material_rígido","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C14","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C14-S004","combinacionSubfamilias":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C14","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C14-S005","combinacionSubfamilias":"Juguetes_y_accesorios_que_dejan_traza + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Juguetes_y_accesorios_que_dejan_traza","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C14","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C14-S006","combinacionSubfamilias":"Juguetes_y_accesorios_que_dejan_traza + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Juguetes_y_accesorios_que_dejan_traza","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C14","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C14-S007","combinacionSubfamilias":"Lazos_y_cuerdas_de_saltar + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Lazos_y_cuerdas_de_saltar","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C14","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C14-S008","combinacionSubfamilias":"Lazos_y_cuerdas_de_saltar + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Lazos_y_cuerdas_de_saltar","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C14","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C14-S009","combinacionSubfamilias":"Proyectiles_de_juguete + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Proyectiles_de_juguete","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":16,"OT":16,"VIG_1":15,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C14","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C14-S010","combinacionSubfamilias":"Proyectiles_de_juguete + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Proyectiles_de_juguete","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":17,"OT":17,"VIG_1":16,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C15","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C15-S001","combinacionSubfamilias":"Juguetes_con_imanes + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Juguetes_con_imanes","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":16,"OT":16,"VIG_1":16,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C15","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C15-S002","combinacionSubfamilias":"Juguetes_con_imanes + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Juguetes_con_imanes","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":17,"OT":17,"VIG_1":17,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C15","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C15-S003","combinacionSubfamilias":"Juguetes_de_material_rígido + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Juguetes_de_material_rígido","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":14,"OT":14,"VIG_1":14,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C15","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C15-S004","combinacionSubfamilias":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":15,"OT":15,"VIG_1":15,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C15","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C15-S005","combinacionSubfamilias":"Juguetes_y_accesorios_que_dejan_traza + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Juguetes_y_accesorios_que_dejan_traza","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":14,"OT":14,"VIG_1":14,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C15","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C15-S006","combinacionSubfamilias":"Juguetes_y_accesorios_que_dejan_traza + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Juguetes_y_accesorios_que_dejan_traza","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":15,"OT":15,"VIG_1":15,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C15","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C15-S007","combinacionSubfamilias":"Lazos_y_cuerdas_de_saltar + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Lazos_y_cuerdas_de_saltar","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":14,"OT":14,"VIG_1":14,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C15","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C15-S008","combinacionSubfamilias":"Lazos_y_cuerdas_de_saltar + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Lazos_y_cuerdas_de_saltar","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":15,"OT":15,"VIG_1":15,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C15","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C15-S009","combinacionSubfamilias":"Proyectiles_de_juguete + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Proyectiles_de_juguete","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":17,"OT":17,"VIG_1":17,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C15","combinacionClasificacion":"Juguetes_de_material_rígido + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C15-S010","combinacionSubfamilias":"Proyectiles_de_juguete + Juguetes_destinados_a_menores_de_36_meses + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juguetes_de_material_rígido","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Proyectiles_de_juguete","Juguetes_destinados_a_menores_de_36_meses","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":18,"OT":18,"VIG_1":18,"VIG_2":1,"RENOVACION":11}},{"idClasificacion":"C16","combinacionClasificacion":"Juguetes_Unidos_a_alimentos + Juguetes_de_material_rígido","idSubcombinacion":"C16-S001","combinacionSubfamilias":"Juguetes_Unidos_a_alimentos + Juguetes_con_imanes","classFamilies":["Juguetes_Unidos_a_alimentos","Juguetes_de_material_rígido"],"subfamilies":["Juguetes_Unidos_a_alimentos","Juguetes_con_imanes"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C16","combinacionClasificacion":"Juguetes_Unidos_a_alimentos + Juguetes_de_material_rígido","idSubcombinacion":"C16-S002","combinacionSubfamilias":"Juguetes_Unidos_a_alimentos + Juguetes_de_material_rígido","classFamilies":["Juguetes_Unidos_a_alimentos","Juguetes_de_material_rígido"],"subfamilies":["Juguetes_Unidos_a_alimentos","Juguetes_de_material_rígido"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C16","combinacionClasificacion":"Juguetes_Unidos_a_alimentos + Juguetes_de_material_rígido","idSubcombinacion":"C16-S003","combinacionSubfamilias":"Juguetes_Unidos_a_alimentos + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Juguetes_Unidos_a_alimentos","Juguetes_de_material_rígido"],"subfamilies":["Juguetes_Unidos_a_alimentos","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C16","combinacionClasificacion":"Juguetes_Unidos_a_alimentos + Juguetes_de_material_rígido","idSubcombinacion":"C16-S004","combinacionSubfamilias":"Juguetes_Unidos_a_alimentos + Lazos_y_cuerdas_de_saltar","classFamilies":["Juguetes_Unidos_a_alimentos","Juguetes_de_material_rígido"],"subfamilies":["Juguetes_Unidos_a_alimentos","Lazos_y_cuerdas_de_saltar"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C16","combinacionClasificacion":"Juguetes_Unidos_a_alimentos + Juguetes_de_material_rígido","idSubcombinacion":"C16-S005","combinacionSubfamilias":"Juguetes_Unidos_a_alimentos + Proyectiles_de_juguete","classFamilies":["Juguetes_Unidos_a_alimentos","Juguetes_de_material_rígido"],"subfamilies":["Juguetes_Unidos_a_alimentos","Proyectiles_de_juguete"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C17","combinacionClasificacion":"Juguetes_Unidos_a_alimentos + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C17-S001","combinacionSubfamilias":"Juguetes_Unidos_a_alimentos + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_Unidos_a_alimentos","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Juguetes_Unidos_a_alimentos","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C17","combinacionClasificacion":"Juguetes_Unidos_a_alimentos + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C17-S002","combinacionSubfamilias":"Juguetes_Unidos_a_alimentos + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Juguetes_Unidos_a_alimentos","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Juguetes_Unidos_a_alimentos","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":16,"OT":16,"VIG_1":15,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C18","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Juguetes_de_material_rígido","idSubcombinacion":"C18-S001","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Juguetes_con_imanes","classFamilies":["Muñecos_Plásticos_Vestidos","Juguetes_de_material_rígido"],"subfamilies":["Muñecos_Plásticos_Vestidos","Juguetes_con_imanes"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C18","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Juguetes_de_material_rígido","idSubcombinacion":"C18-S002","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Juguetes_de_material_rígido","classFamilies":["Muñecos_Plásticos_Vestidos","Juguetes_de_material_rígido"],"subfamilies":["Muñecos_Plásticos_Vestidos","Juguetes_de_material_rígido"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C18","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Juguetes_de_material_rígido","idSubcombinacion":"C18-S003","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Muñecos_Plásticos_Vestidos","Juguetes_de_material_rígido"],"subfamilies":["Muñecos_Plásticos_Vestidos","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C18","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Juguetes_de_material_rígido","idSubcombinacion":"C18-S004","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Lazos_y_cuerdas_de_saltar","classFamilies":["Muñecos_Plásticos_Vestidos","Juguetes_de_material_rígido"],"subfamilies":["Muñecos_Plásticos_Vestidos","Lazos_y_cuerdas_de_saltar"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C18","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Juguetes_de_material_rígido","idSubcombinacion":"C18-S005","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Proyectiles_de_juguete","classFamilies":["Muñecos_Plásticos_Vestidos","Juguetes_de_material_rígido"],"subfamilies":["Muñecos_Plásticos_Vestidos","Proyectiles_de_juguete"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C19","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C19-S001","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Muñecos_Plásticos_Vestidos","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Muñecos_Plásticos_Vestidos","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C19","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C19-S002","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Muñecos_Plásticos_Vestidos","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Muñecos_Plásticos_Vestidos","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C20","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Disfraces_y_accesorios","idSubcombinacion":"C20-S001","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Barbas_bigotes_o_pelucas","classFamilies":["Muñecos_Plásticos_Vestidos","Disfraces_y_accesorios"],"subfamilies":["Muñecos_Plásticos_Vestidos","Barbas_bigotes_o_pelucas"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C20","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Disfraces_y_accesorios","idSubcombinacion":"C20-S002","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Disfraces_y_accesorios_generales","classFamilies":["Muñecos_Plásticos_Vestidos","Disfraces_y_accesorios"],"subfamilies":["Muñecos_Plásticos_Vestidos","Disfraces_y_accesorios_generales"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C20","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Disfraces_y_accesorios","idSubcombinacion":"C20-S003","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Mascaras_y_Cascos","classFamilies":["Muñecos_Plásticos_Vestidos","Disfraces_y_accesorios"],"subfamilies":["Muñecos_Plásticos_Vestidos","Mascaras_y_Cascos"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C21","combinacionClasificacion":"Muñecos_Plásticos_Vestidos + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C21-S001","combinacionSubfamilias":"Muñecos_Plásticos_Vestidos + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Muñecos_Plásticos_Vestidos","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Muñecos_Plásticos_Vestidos","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":9,"OT":9,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C22","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C22-S001","combinacionSubfamilias":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C22","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C22-S002","combinacionSubfamilias":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":10}},{"idClasificacion":"C22","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C22-S003","combinacionSubfamilias":"Juguetes_de_material_flexible + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Juguetes_de_material_flexible","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C22","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C22-S004","combinacionSubfamilias":"Juguetes_de_material_flexible + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Juguetes_de_material_flexible","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C23","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Disfraces_y_accesorios","idSubcombinacion":"C23-S001","combinacionSubfamilias":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Barbas_bigotes_o_pelucas","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Disfraces_y_accesorios"],"subfamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Barbas_bigotes_o_pelucas"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C23","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Disfraces_y_accesorios","idSubcombinacion":"C23-S002","combinacionSubfamilias":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Disfraces_y_accesorios_generales","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Disfraces_y_accesorios"],"subfamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Disfraces_y_accesorios_generales"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C23","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Disfraces_y_accesorios","idSubcombinacion":"C23-S003","combinacionSubfamilias":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Mascaras_y_Cascos","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Disfraces_y_accesorios"],"subfamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Mascaras_y_Cascos"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C23","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Disfraces_y_accesorios","idSubcombinacion":"C23-S004","combinacionSubfamilias":"Juguetes_de_material_flexible + Barbas_bigotes_o_pelucas","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Disfraces_y_accesorios"],"subfamilies":["Juguetes_de_material_flexible","Barbas_bigotes_o_pelucas"],"counts":{"total":5,"OT":5,"VIG_1":4,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C23","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Disfraces_y_accesorios","idSubcombinacion":"C23-S005","combinacionSubfamilias":"Juguetes_de_material_flexible + Disfraces_y_accesorios_generales","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Disfraces_y_accesorios"],"subfamilies":["Juguetes_de_material_flexible","Disfraces_y_accesorios_generales"],"counts":{"total":6,"OT":6,"VIG_1":5,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C23","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Disfraces_y_accesorios","idSubcombinacion":"C23-S006","combinacionSubfamilias":"Juguetes_de_material_flexible + Mascaras_y_Cascos","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Disfraces_y_accesorios"],"subfamilies":["Juguetes_de_material_flexible","Mascaras_y_Cascos"],"counts":{"total":6,"OT":6,"VIG_1":5,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C24","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Juguetes_Unidos_a_alimentos","idSubcombinacion":"C24-S001","combinacionSubfamilias":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Juguetes_Unidos_a_alimentos","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Juguetes_Unidos_a_alimentos"],"subfamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Juguetes_Unidos_a_alimentos"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C24","combinacionClasificacion":"Peluches_Muñecos_Rellenos_y_juguetes_flexibles + Juguetes_Unidos_a_alimentos","idSubcombinacion":"C24-S002","combinacionSubfamilias":"Juguetes_de_material_flexible + Juguetes_Unidos_a_alimentos","classFamilies":["Peluches_Muñecos_Rellenos_y_juguetes_flexibles","Juguetes_Unidos_a_alimentos"],"subfamilies":["Juguetes_de_material_flexible","Juguetes_Unidos_a_alimentos"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S001","combinacionSubfamilias":"Globos_de_Latex + Juguetes_con_imanes","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Globos_de_Latex","Juguetes_con_imanes"],"counts":{"total":10,"OT":10,"VIG_1":10,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S002","combinacionSubfamilias":"Globos_de_Latex + Juguetes_de_material_rígido","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Globos_de_Latex","Juguetes_de_material_rígido"],"counts":{"total":8,"OT":8,"VIG_1":8,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S003","combinacionSubfamilias":"Globos_de_Latex + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Globos_de_Latex","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":4,"OT":4,"VIG_1":4,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S004","combinacionSubfamilias":"Globos_de_Latex + Lazos_y_cuerdas_de_saltar","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Globos_de_Latex","Lazos_y_cuerdas_de_saltar"],"counts":{"total":7,"OT":7,"VIG_1":7,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S005","combinacionSubfamilias":"Globos_de_Latex + Proyectiles_de_juguete","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Globos_de_Latex","Proyectiles_de_juguete"],"counts":{"total":10,"OT":10,"VIG_1":10,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S006","combinacionSubfamilias":"Globos_plásticos + Juguetes_con_imanes","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Globos_plásticos","Juguetes_con_imanes"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S007","combinacionSubfamilias":"Globos_plásticos + Juguetes_de_material_rígido","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Globos_plásticos","Juguetes_de_material_rígido"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S008","combinacionSubfamilias":"Globos_plásticos + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Globos_plásticos","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":5,"OT":5,"VIG_1":4,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S009","combinacionSubfamilias":"Globos_plásticos + Lazos_y_cuerdas_de_saltar","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Globos_plásticos","Lazos_y_cuerdas_de_saltar"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S010","combinacionSubfamilias":"Globos_plásticos + Proyectiles_de_juguete","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Globos_plásticos","Proyectiles_de_juguete"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S011","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Juguetes_con_imanes","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Manillas_gafas_que_alumbran","Juguetes_con_imanes"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S012","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Juguetes_de_material_rígido","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Manillas_gafas_que_alumbran","Juguetes_de_material_rígido"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S013","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Manillas_gafas_que_alumbran","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":4,"OT":4,"VIG_1":3,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S014","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Lazos_y_cuerdas_de_saltar","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Manillas_gafas_que_alumbran","Lazos_y_cuerdas_de_saltar"],"counts":{"total":7,"OT":7,"VIG_1":6,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S015","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Proyectiles_de_juguete","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Manillas_gafas_que_alumbran","Proyectiles_de_juguete"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S016","combinacionSubfamilias":"Papel_y_cartón + Juguetes_con_imanes","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Papel_y_cartón","Juguetes_con_imanes"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S017","combinacionSubfamilias":"Papel_y_cartón + Juguetes_de_material_rígido","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Papel_y_cartón","Juguetes_de_material_rígido"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S018","combinacionSubfamilias":"Papel_y_cartón + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Papel_y_cartón","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":4,"OT":4,"VIG_1":3,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S019","combinacionSubfamilias":"Papel_y_cartón + Lazos_y_cuerdas_de_saltar","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Papel_y_cartón","Lazos_y_cuerdas_de_saltar"],"counts":{"total":7,"OT":7,"VIG_1":6,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S020","combinacionSubfamilias":"Papel_y_cartón + Proyectiles_de_juguete","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Papel_y_cartón","Proyectiles_de_juguete"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S021","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Juguetes_con_imanes","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Pelotas_pequeñas_y_canicas","Juguetes_con_imanes"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S022","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Juguetes_de_material_rígido","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Pelotas_pequeñas_y_canicas","Juguetes_de_material_rígido"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S023","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Pelotas_pequeñas_y_canicas","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":5,"OT":5,"VIG_1":4,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S024","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Lazos_y_cuerdas_de_saltar","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Pelotas_pequeñas_y_canicas","Lazos_y_cuerdas_de_saltar"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S025","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Proyectiles_de_juguete","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Pelotas_pequeñas_y_canicas","Proyectiles_de_juguete"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S026","combinacionSubfamilias":"Piñateria_plastica + Juguetes_con_imanes","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Piñateria_plastica","Juguetes_con_imanes"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S027","combinacionSubfamilias":"Piñateria_plastica + Juguetes_de_material_rígido","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Piñateria_plastica","Juguetes_de_material_rígido"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S028","combinacionSubfamilias":"Piñateria_plastica + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Piñateria_plastica","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":4,"OT":4,"VIG_1":3,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S029","combinacionSubfamilias":"Piñateria_plastica + Lazos_y_cuerdas_de_saltar","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Piñateria_plastica","Lazos_y_cuerdas_de_saltar"],"counts":{"total":7,"OT":7,"VIG_1":6,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C25","combinacionClasificacion":"Piñatería + Juguetes_de_material_rígido","idSubcombinacion":"C25-S030","combinacionSubfamilias":"Piñateria_plastica + Proyectiles_de_juguete","classFamilies":["Piñatería","Juguetes_de_material_rígido"],"subfamilies":["Piñateria_plastica","Proyectiles_de_juguete"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C26","combinacionClasificacion":"Piñatería + Carros_de_juguete_de_cualquier_material","idSubcombinacion":"C26-S001","combinacionSubfamilias":"Globos_de_Latex + Carros_de_juguete_de_cualquier_material","classFamilies":["Piñatería","Carros_de_juguete_de_cualquier_material"],"subfamilies":["Globos_de_Latex","Carros_de_juguete_de_cualquier_material"],"counts":{"total":10,"OT":10,"VIG_1":10,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C26","combinacionClasificacion":"Piñatería + Carros_de_juguete_de_cualquier_material","idSubcombinacion":"C26-S002","combinacionSubfamilias":"Globos_plásticos + Carros_de_juguete_de_cualquier_material","classFamilies":["Piñatería","Carros_de_juguete_de_cualquier_material"],"subfamilies":["Globos_plásticos","Carros_de_juguete_de_cualquier_material"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C26","combinacionClasificacion":"Piñatería + Carros_de_juguete_de_cualquier_material","idSubcombinacion":"C26-S003","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Carros_de_juguete_de_cualquier_material","classFamilies":["Piñatería","Carros_de_juguete_de_cualquier_material"],"subfamilies":["Manillas_gafas_que_alumbran","Carros_de_juguete_de_cualquier_material"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C26","combinacionClasificacion":"Piñatería + Carros_de_juguete_de_cualquier_material","idSubcombinacion":"C26-S004","combinacionSubfamilias":"Papel_y_cartón + Carros_de_juguete_de_cualquier_material","classFamilies":["Piñatería","Carros_de_juguete_de_cualquier_material"],"subfamilies":["Papel_y_cartón","Carros_de_juguete_de_cualquier_material"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C26","combinacionClasificacion":"Piñatería + Carros_de_juguete_de_cualquier_material","idSubcombinacion":"C26-S005","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Carros_de_juguete_de_cualquier_material","classFamilies":["Piñatería","Carros_de_juguete_de_cualquier_material"],"subfamilies":["Pelotas_pequeñas_y_canicas","Carros_de_juguete_de_cualquier_material"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C26","combinacionClasificacion":"Piñatería + Carros_de_juguete_de_cualquier_material","idSubcombinacion":"C26-S006","combinacionSubfamilias":"Piñateria_plastica + Carros_de_juguete_de_cualquier_material","classFamilies":["Piñatería","Carros_de_juguete_de_cualquier_material"],"subfamilies":["Piñateria_plastica","Carros_de_juguete_de_cualquier_material"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C27","combinacionClasificacion":"Piñatería + Muñecos_Plásticos_Vestidos","idSubcombinacion":"C27-S001","combinacionSubfamilias":"Globos_de_Latex + Muñecos_Plásticos_Vestidos","classFamilies":["Piñatería","Muñecos_Plásticos_Vestidos"],"subfamilies":["Globos_de_Latex","Muñecos_Plásticos_Vestidos"],"counts":{"total":8,"OT":8,"VIG_1":8,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C27","combinacionClasificacion":"Piñatería + Muñecos_Plásticos_Vestidos","idSubcombinacion":"C27-S002","combinacionSubfamilias":"Globos_plásticos + Muñecos_Plásticos_Vestidos","classFamilies":["Piñatería","Muñecos_Plásticos_Vestidos"],"subfamilies":["Globos_plásticos","Muñecos_Plásticos_Vestidos"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C27","combinacionClasificacion":"Piñatería + Muñecos_Plásticos_Vestidos","idSubcombinacion":"C27-S003","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Muñecos_Plásticos_Vestidos","classFamilies":["Piñatería","Muñecos_Plásticos_Vestidos"],"subfamilies":["Manillas_gafas_que_alumbran","Muñecos_Plásticos_Vestidos"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C27","combinacionClasificacion":"Piñatería + Muñecos_Plásticos_Vestidos","idSubcombinacion":"C27-S004","combinacionSubfamilias":"Papel_y_cartón + Muñecos_Plásticos_Vestidos","classFamilies":["Piñatería","Muñecos_Plásticos_Vestidos"],"subfamilies":["Papel_y_cartón","Muñecos_Plásticos_Vestidos"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C27","combinacionClasificacion":"Piñatería + Muñecos_Plásticos_Vestidos","idSubcombinacion":"C27-S005","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Muñecos_Plásticos_Vestidos","classFamilies":["Piñatería","Muñecos_Plásticos_Vestidos"],"subfamilies":["Pelotas_pequeñas_y_canicas","Muñecos_Plásticos_Vestidos"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C27","combinacionClasificacion":"Piñatería + Muñecos_Plásticos_Vestidos","idSubcombinacion":"C27-S006","combinacionSubfamilias":"Piñateria_plastica + Muñecos_Plásticos_Vestidos","classFamilies":["Piñatería","Muñecos_Plásticos_Vestidos"],"subfamilies":["Piñateria_plastica","Muñecos_Plásticos_Vestidos"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S001","combinacionSubfamilias":"Globos_de_Latex + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Globos_de_Latex","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"counts":{"total":10,"OT":10,"VIG_1":10,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S002","combinacionSubfamilias":"Globos_de_Latex + Juguetes_de_material_flexible","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Globos_de_Latex","Juguetes_de_material_flexible"],"counts":{"total":5,"OT":5,"VIG_1":5,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S003","combinacionSubfamilias":"Globos_plásticos + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Globos_plásticos","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S004","combinacionSubfamilias":"Globos_plásticos + Juguetes_de_material_flexible","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Globos_plásticos","Juguetes_de_material_flexible"],"counts":{"total":6,"OT":6,"VIG_1":5,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S005","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Manillas_gafas_que_alumbran","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S006","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Juguetes_de_material_flexible","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Manillas_gafas_que_alumbran","Juguetes_de_material_flexible"],"counts":{"total":6,"OT":6,"VIG_1":5,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S007","combinacionSubfamilias":"Papel_y_cartón + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Papel_y_cartón","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S008","combinacionSubfamilias":"Papel_y_cartón + Juguetes_de_material_flexible","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Papel_y_cartón","Juguetes_de_material_flexible"],"counts":{"total":5,"OT":5,"VIG_1":4,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S009","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Pelotas_pequeñas_y_canicas","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"counts":{"total":11,"OT":11,"VIG_1":10,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S010","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Juguetes_de_material_flexible","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Pelotas_pequeñas_y_canicas","Juguetes_de_material_flexible"],"counts":{"total":6,"OT":6,"VIG_1":5,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S011","combinacionSubfamilias":"Piñateria_plastica + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Piñateria_plastica","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"counts":{"total":10,"OT":10,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C28","combinacionClasificacion":"Piñatería + Peluches_Muñecos_Rellenos_y_juguetes_flexibles","idSubcombinacion":"C28-S012","combinacionSubfamilias":"Piñateria_plastica + Juguetes_de_material_flexible","classFamilies":["Piñatería","Peluches_Muñecos_Rellenos_y_juguetes_flexibles"],"subfamilies":["Piñateria_plastica","Juguetes_de_material_flexible"],"counts":{"total":6,"OT":6,"VIG_1":5,"VIG_2":1,"RENOVACION":3}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S001","combinacionSubfamilias":"Globos_de_Latex + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Globos_de_Latex","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":13,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S002","combinacionSubfamilias":"Globos_de_Latex + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Globos_de_Latex","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":14,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S003","combinacionSubfamilias":"Globos_plásticos + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Globos_plásticos","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S004","combinacionSubfamilias":"Globos_plásticos + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Globos_plásticos","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S005","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Manillas_gafas_que_alumbran","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S006","combinacionSubfamilias":"Manillas_gafas_que_alumbran + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Manillas_gafas_que_alumbran","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S007","combinacionSubfamilias":"Papel_y_cartón + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Papel_y_cartón","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S008","combinacionSubfamilias":"Papel_y_cartón + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Papel_y_cartón","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S009","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Pelotas_pequeñas_y_canicas","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S010","combinacionSubfamilias":"Pelotas_pequeñas_y_canicas + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Pelotas_pequeñas_y_canicas","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":15,"OT":15,"VIG_1":14,"VIG_2":1,"RENOVACION":9}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S011","combinacionSubfamilias":"Piñateria_plastica + Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Piñateria_plastica","Juguetes_con_cables_o_cuerdas_destinados_a_menores_de_36_meses"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C29","combinacionClasificacion":"Piñatería + Juguetes_destinados_a_menores_de_36_meses","idSubcombinacion":"C29-S012","combinacionSubfamilias":"Piñateria_plastica + Juguetes_destinados_a_menores_de_36_meses","classFamilies":["Piñatería","Juguetes_destinados_a_menores_de_36_meses"],"subfamilies":["Piñateria_plastica","Juguetes_destinados_a_menores_de_36_meses"],"counts":{"total":14,"OT":14,"VIG_1":13,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C30","combinacionClasificacion":"Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","idSubcombinacion":"C30-S001","combinacionSubfamilias":"Juguetes_que_contienen_liquido_o_gel + Juguetes_con_imanes","classFamilies":["Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"subfamilies":["Juguetes_que_contienen_liquido_o_gel","Juguetes_con_imanes"],"counts":{"total":11,"OT":11,"VIG_1":11,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C30","combinacionClasificacion":"Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","idSubcombinacion":"C30-S002","combinacionSubfamilias":"Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","classFamilies":["Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"subfamilies":["Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"counts":{"total":9,"OT":9,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C30","combinacionClasificacion":"Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","idSubcombinacion":"C30-S003","combinacionSubfamilias":"Juguetes_que_contienen_liquido_o_gel + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"subfamilies":["Juguetes_que_contienen_liquido_o_gel","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":5,"OT":5,"VIG_1":5,"VIG_2":1,"RENOVACION":4}},{"idClasificacion":"C30","combinacionClasificacion":"Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","idSubcombinacion":"C30-S004","combinacionSubfamilias":"Juguetes_que_contienen_liquido_o_gel + Lazos_y_cuerdas_de_saltar","classFamilies":["Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"subfamilies":["Juguetes_que_contienen_liquido_o_gel","Lazos_y_cuerdas_de_saltar"],"counts":{"total":8,"OT":8,"VIG_1":8,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C30","combinacionClasificacion":"Juguetes_que_contienen_liquido_o_gel + Juguetes_de_material_rígido","idSubcombinacion":"C30-S005","combinacionSubfamilias":"Juguetes_que_contienen_liquido_o_gel + Proyectiles_de_juguete","classFamilies":["Juguetes_que_contienen_liquido_o_gel","Juguetes_de_material_rígido"],"subfamilies":["Juguetes_que_contienen_liquido_o_gel","Proyectiles_de_juguete"],"counts":{"total":11,"OT":11,"VIG_1":11,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C31","combinacionClasificacion":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Juguetes_de_material_rígido","idSubcombinacion":"C31-S001","combinacionSubfamilias":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Juguetes_con_imanes","classFamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Juguetes_de_material_rígido"],"subfamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Juguetes_con_imanes"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C31","combinacionClasificacion":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Juguetes_de_material_rígido","idSubcombinacion":"C31-S002","combinacionSubfamilias":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Juguetes_de_material_rígido","classFamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Juguetes_de_material_rígido"],"subfamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Juguetes_de_material_rígido"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C31","combinacionClasificacion":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Juguetes_de_material_rígido","idSubcombinacion":"C31-S003","combinacionSubfamilias":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Juguetes_de_material_rígido"],"subfamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C31","combinacionClasificacion":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Juguetes_de_material_rígido","idSubcombinacion":"C31-S004","combinacionSubfamilias":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Lazos_y_cuerdas_de_saltar","classFamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Juguetes_de_material_rígido"],"subfamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Lazos_y_cuerdas_de_saltar"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C31","combinacionClasificacion":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Juguetes_de_material_rígido","idSubcombinacion":"C31-S005","combinacionSubfamilias":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Proyectiles_de_juguete","classFamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Juguetes_de_material_rígido"],"subfamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Proyectiles_de_juguete"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C32","combinacionClasificacion":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C32-S001","combinacionSubfamilias":"Juegos_de_experimentos_químicos_y_actividades_relacionadas + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Juegos_de_experimentos_químicos_y_actividades_relacionadas","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":9,"OT":9,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C33","combinacionClasificacion":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Juguetes_que_contienen_liquido_o_gel","idSubcombinacion":"C33-S001","combinacionSubfamilias":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Juguetes_que_contienen_liquido_o_gel","classFamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Juguetes_que_contienen_liquido_o_gel"],"subfamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Juguetes_que_contienen_liquido_o_gel"],"counts":{"total":9,"OT":9,"VIG_1":9,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C34","combinacionClasificacion":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Juguetes_de_material_rígido","idSubcombinacion":"C34-S001","combinacionSubfamilias":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Juguetes_con_imanes","classFamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Juguetes_de_material_rígido"],"subfamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Juguetes_con_imanes"],"counts":{"total":13,"OT":13,"VIG_1":12,"VIG_2":1,"RENOVACION":8}},{"idClasificacion":"C34","combinacionClasificacion":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Juguetes_de_material_rígido","idSubcombinacion":"C34-S002","combinacionSubfamilias":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Juguetes_de_material_rígido","classFamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Juguetes_de_material_rígido"],"subfamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Juguetes_de_material_rígido"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":7}},{"idClasificacion":"C34","combinacionClasificacion":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Juguetes_de_material_rígido","idSubcombinacion":"C34-S003","combinacionSubfamilias":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Juguetes_y_accesorios_que_dejan_traza","classFamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Juguetes_de_material_rígido"],"subfamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Juguetes_y_accesorios_que_dejan_traza"],"counts":{"total":8,"OT":8,"VIG_1":7,"VIG_2":1,"RENOVACION":5}},{"idClasificacion":"C34","combinacionClasificacion":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Juguetes_de_material_rígido","idSubcombinacion":"C34-S004","combinacionSubfamilias":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Lazos_y_cuerdas_de_saltar","classFamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Juguetes_de_material_rígido"],"subfamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Lazos_y_cuerdas_de_saltar"],"counts":{"total":9,"OT":9,"VIG_1":8,"VIG_2":1,"RENOVACION":6}},{"idClasificacion":"C34","combinacionClasificacion":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Juguetes_de_material_rígido","idSubcombinacion":"C34-S005","combinacionSubfamilias":"Juegos_químicos_distintos_de_los_juegos_de_experimento + Proyectiles_de_juguete","classFamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Juguetes_de_material_rígido"],"subfamilies":["Juegos_químicos_distintos_de_los_juegos_de_experimento","Proyectiles_de_juguete"],"counts":{"total":12,"OT":12,"VIG_1":11,"VIG_2":1,"RENOVACION":6}}]}</script>
  <script>
    const DATA = JSON.parse(document.getElementById('app-data').textContent);

    const PHASES = [
      { key: 'OT', label: 'OT' },
      { key: 'VIG_1', label: 'Vigilancia 1' },
      { key: 'VIG_2', label: 'Vigilancia 2' },
      { key: 'RENOVACION', label: 'Renovación' }
    ];

    const STORAGE_KEY = 'constructor_iso_juguetes_v1';

    const state = {
      currentToy: newToy(),
      savedToys: [],
      results: [],
      metrics: null
    };

    const $ = (id) => document.getElementById(id);
    const collator = new Intl.Collator('es', { sensitivity: 'base', numeric: true });

    const familyByNorm = new Map();
    const subfamilyByNorm = new Map();
    const subfamiliesByFamily = new Map();
    const testsBySubfamily = new Map();
    const combosByClass = new Map();

    function newToy(name = '') {
      return {
        id: crypto.randomUUID ? crypto.randomUUID() : String(Date.now() + Math.random()),
        name,
        subfamilies: []
      };
    }

    function normalizeKey(value) {
      return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
    }

    function uniqueSorted(values) {
      return [...new Set(values.filter(Boolean).map(v => String(v).trim()).filter(Boolean))]
        .sort((a, b) => collator.compare(a, b));
    }

    function humanizeNorm(norm) {
      return String(norm || '')
        .replaceAll('_', ' ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function subLabel(norm) {
      return subfamilyByNorm.get(norm)?.subfamilia || humanizeNorm(norm);
    }

    function familyLabel(norm) {
      return familyByNorm.get(norm)?.familia || humanizeNorm(norm);
    }

    function escapeHtml(value) {
      return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function phaseBadges(row) {
      const active = PHASES.filter(p => row[p.key]).map(p => p.label);
      if (!active.length) return '<span class="muted">Sin fase marcada</span>';
      return active.map(p => `<span class="pill ok">${escapeHtml(p)}</span>`).join(' ');
    }

    function joinSmall(values, separator = ' · ') {
      const list = Array.isArray(values) ? values : [];
      return list.length ? escapeHtml(list.join(separator)) : '<span class="muted">—</span>';
    }

    function initMaps() {
      DATA.subfamilies.forEach(item => {
        familyByNorm.set(item.familiaNorm, item);
        subfamilyByNorm.set(item.subfamiliaNorm, item);
        if (!subfamiliesByFamily.has(item.familiaNorm)) subfamiliesByFamily.set(item.familiaNorm, []);
        subfamiliesByFamily.get(item.familiaNorm).push(item);
      });

      for (const list of subfamiliesByFamily.values()) {
        list.sort((a, b) => collator.compare(a.subfamilia, b.subfamilia));
      }

      DATA.tests.forEach(item => {
        if (!testsBySubfamily.has(item.subfamiliaNorm)) testsBySubfamily.set(item.subfamiliaNorm, []);
        testsBySubfamily.get(item.subfamiliaNorm).push(item);
      });

      DATA.combinations.forEach(combo => {
        if (!combosByClass.has(combo.idClasificacion)) combosByClass.set(combo.idClasificacion, []);
        combosByClass.get(combo.idClasificacion).push(combo);
      });
    }

    function loadState() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed.savedToys)) state.savedToys = parsed.savedToys;
        if (parsed.currentToy && Array.isArray(parsed.currentToy.subfamilies)) state.currentToy = parsed.currentToy;
      } catch (error) {
        console.warn('No se pudo cargar estado local:', error);
      }
    }

    function persistState() {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
          currentToy: state.currentToy,
          savedToys: state.savedToys
        }));
      } catch (error) {
        console.warn('No se pudo guardar estado local:', error);
      }
    }

    function renderStats() {
      const stats = DATA.meta.stats;
      const items = [
        [stats.familias, 'clases / familias'],
        [stats.subfamiliasBase, 'subfamilias base'],
        [stats.combinacionesClasificacion, 'combinaciones de clase'],
        [stats.combinacionesSubfamilia, 'subcombinaciones'],
        [stats.ensayosBaseUnicos, 'ensayos base únicos'],
        [stats.registrosBase, 'registros base']
      ];

      $('stats').innerHTML = items.map(([value, label]) => `
        <div class="stat">
          <strong>${escapeHtml(value)}</strong>
          <span>${escapeHtml(label)}</span>
        </div>
      `).join('');

      $('metaNotes').innerHTML = [
        DATA.meta.fases,
        DATA.meta.notaMaterial,
        DATA.meta.deduplicacion
      ].filter(Boolean).map(note => `<span class="pill neutral">${escapeHtml(note)}</span>`).join('');
    }

    function renderClassSelect() {
      const firstCombos = [...combosByClass.entries()].map(([id, list]) => list[0]);
      firstCombos.sort((a, b) => collator.compare(a.idClasificacion, b.idClasificacion));

      $('classComboSelect').innerHTML = firstCombos.map(combo => {
        const label = combo.classFamilies.map(familyLabel).join(' + ');
        const count = combosByClass.get(combo.idClasificacion).length;
        return `<option value="${escapeHtml(combo.idClasificacion)}">${escapeHtml(combo.idClasificacion)} · ${escapeHtml(label)} (${count})</option>`;
      }).join('');

      updateSubcomboSelect();
    }

    function renderFamilySelect() {
      const families = [...subfamiliesByFamily.keys()].sort((a, b) => collator.compare(familyLabel(a), familyLabel(b)));
      $('familySelect').innerHTML = families.map(fam => `<option value="${escapeHtml(fam)}">${escapeHtml(familyLabel(fam))}</option>`).join('');
      renderSubfamilyChecks();
    }

    function updateSubcomboSelect() {
      const classId = $('classComboSelect').value;
      const list = combosByClass.get(classId) || [];
      const current = list[0];

      $('classComboInfo').innerHTML = current
        ? `<strong>${escapeHtml(current.idClasificacion)}</strong><br>${escapeHtml(current.classFamilies.map(familyLabel).join(' + '))}<div class="mini">${list.length} subcombinaciones disponibles para esta clasificación.</div>`
        : '<span class="muted">Selecciona una clasificación.</span>';

      $('subComboSelect').innerHTML = list.map(combo => {
        const label = combo.subfamilies.map(subLabel).join(' + ');
        return `<option value="${escapeHtml(combo.idSubcombinacion)}">${escapeHtml(combo.idSubcombinacion)} · ${escapeHtml(label)}</option>`;
      }).join('');

      updateSubcomboInfo();
    }

    function selectedSubcombo() {
      const classId = $('classComboSelect').value;
      const subId = $('subComboSelect').value;
      return (combosByClass.get(classId) || []).find(item => item.idSubcombinacion === subId);
    }

    function updateSubcomboInfo() {
      const combo = selectedSubcombo();
      if (!combo) {
        $('subComboInfo').innerHTML = '<span class="muted">Sin subcombinación seleccionada.</span>';
        return;
      }

      const countLine = [
        `Total: ${combo.counts.total}`,
        `OT: ${combo.counts.OT}`,
        `VIG 1: ${combo.counts.VIG_1}`,
        `VIG 2: ${combo.counts.VIG_2}`,
        `Renovación: ${combo.counts.RENOVACION}`
      ].join(' · ');

      $('subComboInfo').innerHTML = `
        <strong>${escapeHtml(combo.idSubcombinacion)}</strong><br>
        ${escapeHtml(combo.subfamilies.map(subLabel).join(' + '))}
        <div class="pill-list">
          ${combo.subfamilies.map(sub => `<span class="pill">${escapeHtml(subLabel(sub))}</span>`).join('')}
        </div>
        <div class="mini" style="margin-top:.6rem;">Ensayos precontados en matriz: ${escapeHtml(countLine)}</div>
      `;
    }

    function renderSubfamilyChecks() {
      const fam = $('familySelect').value;
      const subs = subfamiliesByFamily.get(fam) || [];

      $('subfamilyChecks').innerHTML = subs.map(item => `
        <label class="check-card">
          <input type="checkbox" value="${escapeHtml(item.subfamiliaNorm)}">
          <span>
            <strong>${escapeHtml(item.subfamilia)}</strong>
            <span>${escapeHtml(item.cantidadEnsayosBase)} ensayos base · ${escapeHtml(item.familia)}</span>
          </span>
        </label>
      `).join('') || '<div class="empty">No hay subfamilias para esta familia.</div>';
    }

    function addSubfamiliesToCurrent(subfamilies, sourceLabel = 'selección') {
      const before = state.currentToy.subfamilies.length;
      const set = new Set(state.currentToy.subfamilies);
      subfamilies.filter(Boolean).forEach(sub => set.add(sub));
      state.currentToy.subfamilies = [...set].sort((a, b) => collator.compare(subLabel(a), subLabel(b)));
      const added = state.currentToy.subfamilies.length - before;
      renderCurrentToy();
      persistState();
      showToast(added ? `${added} subfamilia(s) agregada(s) desde ${sourceLabel}.` : 'Estas subfamilias ya estaban agregadas.');
    }

    function renderCurrentToy() {
      const input = $('toyNameInput');
      if (document.activeElement !== input) {
        input.value = state.currentToy.name || defaultToyName();
      }

      $('currentToyCount').textContent = `${state.currentToy.subfamilies.length} subfamilia(s)`;

      if (!state.currentToy.subfamilies.length) {
        $('currentToyChips').innerHTML = '<div class="empty">Todavía no has agregado subfamilias al juguete actual.</div>';
        return;
      }

      $('currentToyChips').innerHTML = state.currentToy.subfamilies.map(sub => `
        <span class="pill">
          ${escapeHtml(subLabel(sub))}
          <button type="button" title="Quitar" data-remove-sub="${escapeHtml(sub)}">×</button>
        </span>
      `).join('');
    }

    function defaultToyName() {
      return `Juguete ${state.savedToys.length + 1}`;
    }

    function saveCurrentToy() {
      const name = $('toyNameInput').value.trim() || defaultToyName();

      if (!state.currentToy.subfamilies.length) {
        showToast('Agrega al menos una subfamilia antes de guardar el juguete.');
        return;
      }

      state.savedToys.push({
        id: state.currentToy.id,
        name,
        subfamilies: [...state.currentToy.subfamilies]
      });

      state.currentToy = newToy(`Juguete ${state.savedToys.length + 1}`);
      renderCurrentToy();
      renderSavedToys();
      persistState();
      showToast('Juguete guardado en la lista.');
    }

    function renderSavedToys() {
      if (!state.savedToys.length) {
        $('savedToys').innerHTML = '<div class="empty">Aún no hay juguetes guardados. Puedes calcular también con el juguete actual si tiene componentes.</div>';
        return;
      }

      $('savedToys').innerHTML = state.savedToys.map(toy => {
        const chips = toy.subfamilies.slice(0, 8).map(sub => `<span class="pill neutral">${escapeHtml(subLabel(sub))}</span>`).join('');
        const rest = toy.subfamilies.length > 8 ? `<span class="pill neutral">+${toy.subfamilies.length - 8} más</span>` : '';
        return `
          <article class="toy-card">
            <header>
              <div>
                <h3>${escapeHtml(toy.name)}</h3>
                <small>${toy.subfamilies.length} subfamilia(s)</small>
              </div>
            </header>
            <div class="pill-list">${chips}${rest}</div>
            <div class="actions">
              <button type="button" class="secondary" data-load-toy="${escapeHtml(toy.id)}">Cargar copia</button>
              <button type="button" class="ghost" data-delete-toy="${escapeHtml(toy.id)}">Eliminar</button>
            </div>
          </article>
        `;
      }).join('');
    }

    function toysForCalculation() {
      const toys = state.savedToys.map(toy => ({
        id: toy.id,
        name: toy.name,
        subfamilies: [...toy.subfamilies]
      }));

      if (state.currentToy.subfamilies.length) {
        toys.push({
          id: state.currentToy.id,
          name: $('toyNameInput').value.trim() || state.currentToy.name || 'Juguete actual',
          subfamilies: [...state.currentToy.subfamilies]
        });
      }

      return toys;
    }

    function calculateResults() {
      const toys = toysForCalculation();

      if (!toys.length) {
        state.results = [];
        state.metrics = null;
        renderResults();
        showToast('Agrega o guarda al menos un juguete para calcular ensayos.');
        return;
      }

      const aggregated = new Map();
      let sourceRows = 0;
      const uniqueSubfamilies = new Set();

      toys.forEach(toy => {
        uniqueSorted(toy.subfamilies).forEach(sub => {
          uniqueSubfamilies.add(sub);
          const tests = testsBySubfamily.get(sub) || [];

          tests.forEach(test => {
            if (!test.ensayo) return;
            sourceRows += 1;
            const key = normalizeKey(test.ensayo);

            if (!aggregated.has(key)) {
              aggregated.set(key, {
                key,
                ensayo: test.ensayo,
                literales: new Set(),
                normas: new Set(),
                subfamilias: new Set(),
                familias: new Set(),
                filasFuente: new Set(),
                juguetes: new Set(),
                OT: false,
                VIG_1: false,
                VIG_2: false,
                RENOVACION: false
              });
            }

            const row = aggregated.get(key);
            if (test.literal) row.literales.add(test.literal);
            if (test.norma) row.normas.add(test.norma);
            if (test.subfamiliaNorm) row.subfamilias.add(subLabel(test.subfamiliaNorm));
            if (test.familiaNorm) row.familias.add(familyLabel(test.familiaNorm));
            if (test.filaFuente) row.filasFuente.add(String(test.filaFuente));
            row.juguetes.add(toy.name);
            PHASES.forEach(p => {
              row[p.key] = row[p.key] || Boolean(test[p.key]);
            });
          });
        });
      });

      const results = [...aggregated.values()]
        .map(row => ({
          ...row,
          literales: uniqueSorted([...row.literales]),
          normas: uniqueSorted([...row.normas]),
          subfamilias: uniqueSorted([...row.subfamilias]),
          familias: uniqueSorted([...row.familias]),
          filasFuente: uniqueSorted([...row.filasFuente]),
          juguetes: uniqueSorted([...row.juguetes])
        }))
        .sort((a, b) => collator.compare(a.ensayo, b.ensayo));

      const phaseCounts = Object.fromEntries(PHASES.map(p => [p.key, results.filter(row => row[p.key]).length]));

      state.results = results;
      state.metrics = {
        toys: toys.length,
        selectedSubfamilies: uniqueSubfamilies.size,
        sourceRows,
        uniqueEssays: results.length,
        duplicatesRemoved: Math.max(sourceRows - results.length, 0),
        phaseCounts
      };

      renderToyFilter(toys);
      renderResults();
      showToast(`${results.length} ensayo(s) único(s) calculado(s).`);
      $('resultsPanel').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderToyFilter(toys = toysForCalculation()) {
      const names = uniqueSorted(toys.map(toy => toy.name));
      $('toyFilter').innerHTML = '<option value="ALL">Todos los juguetes</option>' + names.map(name => `<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`).join('');
    }

    function filteredResults() {
      const query = normalizeKey($('searchInput').value);
      const phase = $('phaseFilter').value;
      const toy = $('toyFilter').value;

      return state.results.filter(row => {
        if (phase !== 'ALL' && !row[phase]) return false;
        if (toy !== 'ALL' && !row.juguetes.includes(toy)) return false;

        if (query) {
          const haystack = normalizeKey([
            row.ensayo,
            row.literales.join(' '),
            row.normas.join(' '),
            row.subfamilias.join(' '),
            row.familias.join(' '),
            row.juguetes.join(' ')
          ].join(' '));
          if (!haystack.includes(query)) return false;
        }

        return true;
      });
    }

    function renderResultStats(filtered = filteredResults()) {
      const m = state.metrics;
      if (!m) {
        $('resultStats').innerHTML = '';
        return;
      }

      const items = [
        [m.toys, 'juguetes evaluados'],
        [m.selectedSubfamilies, 'subfamilias únicas'],
        [m.uniqueEssays, 'ensayos sin repetir'],
        [m.sourceRows, 'registros fuente usados'],
        [m.duplicatesRemoved, 'duplicados consolidados']
      ];

      $('resultStats').innerHTML = items.map(([value, label]) => `
        <div class="result-stat">
          <strong>${escapeHtml(value)}</strong>
          <span>${escapeHtml(label)}</span>
        </div>
      `).join('') + `
        <div class="result-stat">
          <strong>${escapeHtml(filtered.length)}</strong>
          <span>ensayos visibles con filtros</span>
        </div>
        ${PHASES.map(p => `
          <div class="result-stat">
            <strong>${escapeHtml(m.phaseCounts[p.key] || 0)}</strong>
            <span>${escapeHtml(p.label)}</span>
          </div>
        `).join('')}
      `;
    }

    function renderResults() {
      const rows = filteredResults();
      renderResultStats(rows);

      if (!state.metrics) {
        $('resultHint').textContent = 'Agrega al menos un juguete y presiona “Calcular ensayos sin repetir”.';
      } else {
        $('resultHint').textContent = `Resultado calculado con ${state.metrics.toys} juguete(s). La tabla elimina duplicados por ensayo y agrupa fuentes.`;
      }

      if (!rows.length) {
        $('resultsBody').innerHTML = `<tr><td colspan="7" class="empty">${state.results.length ? 'No hay ensayos que coincidan con los filtros.' : 'Sin resultados calculados.'}</td></tr>`;
        return;
      }

      $('resultsBody').innerHTML = rows.map((row, index) => `
        <tr>
          <td>${index + 1}</td>
          <td>
            <div class="essay-name">${escapeHtml(row.ensayo)}</div>
            <div class="mini">Filas fuente: ${joinSmall(row.filasFuente, ' | ')}</div>
          </td>
          <td>${joinSmall(row.literales, ' | ')}</td>
          <td>${joinSmall(row.normas, ' | ')}</td>
          <td><div class="pill-list">${phaseBadges(row)}</div></td>
          <td>
            <strong>Subfamilias:</strong><br>${joinSmall(row.subfamilias, ' | ')}
            <div class="mini"><strong>Familias:</strong> ${joinSmall(row.familias, ' | ')}</div>
          </td>
          <td>${joinSmall(row.juguetes, ' | ')}</td>
        </tr>
      `).join('');
    }

    function exportRows() {
      return filteredResults().map((row, index) => ({
        "#": index + 1,
        "Ensayo único": row.ensayo,
        "Literal / numeral": row.literales.join(' | '),
        "Norma técnica": row.normas.join(' | '),
        "Fases": PHASES.filter(p => row[p.key]).map(p => p.label).join(' | '),
        "Subfamilias fuente": row.subfamilias.join(' | '),
        "Familias fuente": row.familias.join(' | '),
        "Juguetes": row.juguetes.join(' | '),
        "Filas fuente": row.filasFuente.join(' | ')
      }));
    }

    function downloadBlob(filename, content, type) {
      const blob = new Blob([content], { type });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    }

    function exportCsv() {
      const rows = exportRows();
      if (!rows.length) {
        showToast('No hay resultados para exportar.');
        return;
      }
      const headers = Object.keys(rows[0]);
      const csv = [
        headers.join(';'),
        ...rows.map(row => headers.map(h => csvCell(row[h])).join(';'))
      ].join('\n');
      downloadBlob('ensayos_sin_repetir.csv', '\ufeff' + csv, 'text/csv;charset=utf-8');
    }

    function csvCell(value) {
      const text = String(value ?? '');
      if (/[;"\n\r]/.test(text)) return `"${text.replaceAll('"', '""')}"`;
      return text;
    }

    function exportJson() {
      const payload = {
        generadoEn: new Date().toISOString(),
        meta: DATA.meta,
        metricas: state.metrics,
        juguetes: toysForCalculation(),
        resultados: exportRows()
      };
      downloadBlob('ensayos_sin_repetir.json', JSON.stringify(payload, null, 2), 'application/json;charset=utf-8');
    }

    function resetCurrentToy() {
      state.currentToy = newToy(defaultToyName());
      renderCurrentToy();
      persistState();
      showToast('Constructor listo para un nuevo juguete.');
    }

    function clearAll() {
      const ok = confirm('¿Seguro que quieres limpiar juguetes, constructor y resultados?');
      if (!ok) return;

      state.currentToy = newToy('Juguete 1');
      state.savedToys = [];
      state.results = [];
      state.metrics = null;
      localStorage.removeItem(STORAGE_KEY);
      renderCurrentToy();
      renderSavedToys();
      renderToyFilter([]);
      renderResults();
      showToast('Proyecto limpiado.');
    }

    function showToast(message) {
      const toast = $('toast');
      toast.textContent = message;
      toast.classList.add('show');
      window.clearTimeout(showToast.timer);
      showToast.timer = window.setTimeout(() => toast.classList.remove('show'), 2600);
    }

    function bindEvents() {
      $('classComboSelect').addEventListener('change', updateSubcomboSelect);
      $('subComboSelect').addEventListener('change', updateSubcomboInfo);
      $('familySelect').addEventListener('change', renderSubfamilyChecks);

      $('loadSubcomboBtn').addEventListener('click', () => {
        const combo = selectedSubcombo();
        if (!combo) return;
        addSubfamiliesToCurrent(combo.subfamilies, combo.idSubcombinacion);
      });

      $('saveSubcomboToyBtn').addEventListener('click', () => {
        const combo = selectedSubcombo();
        if (!combo) return;
        const toy = {
          id: crypto.randomUUID ? crypto.randomUUID() : String(Date.now() + Math.random()),
          name: combo.idSubcombinacion,
          subfamilies: uniqueSorted(combo.subfamilies)
        };
        state.savedToys.push(toy);
        renderSavedToys();
        persistState();
        showToast(`${combo.idSubcombinacion} guardado como juguete.`);
      });

      $('addManualBtn').addEventListener('click', () => {
        const selected = [...$('subfamilyChecks').querySelectorAll('input:checked')].map(input => input.value);
        if (!selected.length) {
          showToast('Marca al menos una subfamilia.');
          return;
        }
        addSubfamiliesToCurrent(selected, 'selección manual');
      });

      $('clearChecksBtn').addEventListener('click', () => {
        $('subfamilyChecks').querySelectorAll('input:checked').forEach(input => input.checked = false);
      });

      $('toyNameInput').addEventListener('input', () => {
        state.currentToy.name = $('toyNameInput').value;
        persistState();
      });

      $('currentToyChips').addEventListener('click', (event) => {
        const sub = event.target?.dataset?.removeSub;
        if (!sub) return;
        state.currentToy.subfamilies = state.currentToy.subfamilies.filter(item => item !== sub);
        renderCurrentToy();
        persistState();
      });

      $('saveCurrentToyBtn').addEventListener('click', saveCurrentToy);
      $('resetCurrentBtn').addEventListener('click', resetCurrentToy);
      $('calculateBtn').addEventListener('click', calculateResults);
      $('clearAllBtn').addEventListener('click', clearAll);

      $('savedToys').addEventListener('click', (event) => {
        const deleteId = event.target?.dataset?.deleteToy;
        const loadId = event.target?.dataset?.loadToy;

        if (deleteId) {
          state.savedToys = state.savedToys.filter(toy => toy.id !== deleteId);
          renderSavedToys();
          persistState();
          showToast('Juguete eliminado.');
        }

        if (loadId) {
          const toy = state.savedToys.find(item => item.id === loadId);
          if (!toy) return;
          state.currentToy = {
            id: crypto.randomUUID ? crypto.randomUUID() : String(Date.now() + Math.random()),
            name: `${toy.name} (copia)`,
            subfamilies: [...toy.subfamilies]
          };
          renderCurrentToy();
          persistState();
          showToast('Copia cargada en el constructor.');
        }
      });

      ['searchInput', 'phaseFilter', 'toyFilter'].forEach(id => {
        $(id).addEventListener('input', renderResults);
        $(id).addEventListener('change', renderResults);
      });

      $('exportCsvBtn').addEventListener('click', exportCsv);
      $('exportJsonBtn').addEventListener('click', exportJson);
      $('printBtn').addEventListener('click', () => window.print());
    }

    function init() {
      initMaps();
      loadState();
      renderStats();
      renderClassSelect();
      renderFamilySelect();
      renderCurrentToy();
      renderSavedToys();
      renderToyFilter();
      renderResults();
      bindEvents();
    }

    init();
  </script>
</body>
</html>
