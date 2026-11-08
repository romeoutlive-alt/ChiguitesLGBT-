'use strict';
// Propuestas locales y conservadoras basadas en nombres, nunca en contenidos.
window.sedeClasificar = (() => {
 const norm = s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
 const rx = (s,r) => r.test(s);
 function classify(path) {
  const s=norm(path.join(' / '));
  if(rx(s,/subvenci|convocatoria.*igualdad|ayudas? economica/)){
   const years=[...s.matchAll(/\b(2015|2019|2021|2024|2025|2026)\b/g)].map(x=>x[1]);
   if(!years.length||new Set(years).size!==1)return 'sub-otras';
   const prefix='sub-'+years[0];
   if(rx(s,/justificaci|memoria justificativa/))return prefix+'-justificacion';
   if(rx(s,/resoluci[oó]n|aceptaci[oó]n|concesi[oó]n/))return prefix+'-resolucion';
   if(rx(s,/convocatoria|\bbases\b/))return prefix+'-convocatoria';
   if(rx(s,/solicitud|anexos?/))return prefix+'-solicitud';
   return prefix;
  }
  if(rx(s,/curso[s]? .*felgtbi|curso vih|curso lgtbfobia|proteccion internacional/))return 'cursos-felgtbi';
  if(rx(s,/escuela de otono.*salud|salud sexual.*escuela de otono/))return 'escuela-salud';
  if(rx(s,/materiales charlas|charlas diversidad|taller chiguit|charlas eso/))return 'charlas';
  if(rx(s,/consejo federal/))return 'consejos';
  if(rx(s,/felgtbi.*(avales|ingreso)|avales.*felgtbi/))return 'felgtbi';
  if(rx(s,/documentos fundacionales|estatutos|reglamento/))return 'estatutos';
  if(rx(s,/cambios? (comision ejecutiva|coordinadora)|cambio ej |cambio ejecutiva/))return 'ejecutiva';
  if(rx(s,/solicitud sede social/))return 'sede-social';
  if(rx(s,/politica de privacidad|proteccion de datos/))return 'privacidad';
  if(rx(s,/carne socios|carnes socios|carne de soci/))return 'carnes';
  if(rx(s,/palencia queerida/))return 'queerida';
  if(rx(s,/solicitudes? concentraciones?/))return 'permisos';
  if(rx(s,/orgullo/)){
   const years=[...s.matchAll(/\b(2023|2024|2025)\b/g)].map(x=>x[1]);
   return new Set(years).size===1?'orgullo-'+years[0]:'orgullo';
  }
  if(rx(s,/previhene/))return 'previhene';
  if(rx(s,/pruebas vih|red ?covih/))return 'pruebas';
  if(rx(s,/siluetas/))return 'siluetas';
  if(rx(s,/\bimprimir\b|material para imprenta/))return 'impresion';
  if(rx(s,/pegatinas|folletos?|codigos? qr/))return 'folletos';
  if(rx(s,/materiales de difusion|logos de chiguites|logos de entidades|membrete de chiguit/))return 'identidad';
  if(rx(s,/\bmanifiestos?\b/))return 'campanas';
  if(rx(s,/solicitudes? ayuntamiento|instancias? genericas? ayuntamiento/))return 'registro';
  if(rx(s,/protocolos? secretaria/))return 'altas';
  if(rx(s,/\bcompras\b|\bfacturas\b/))return 'compras';
  return 'pendientes';
 }
 return {classify};
})();
