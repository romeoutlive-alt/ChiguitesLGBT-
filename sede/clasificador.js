'use strict';
// Se usan solo nombres y rutas, no el contenido. La carpeta de origen manda.
window.sedeClasificar = (() => {
 const norm=s=>(s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
 const has=(s,r)=>r.test(s);
 const stage=s=>has(s,/justificaci|memoria justificativa/)?'justificacion':has(s,/resolucion|aceptacion|concesion|anticipo/)?'resolucion':has(s,/convocatoria|\bbases\b/)?'convocatoria':has(s,/solicitud|\banexos?\b/)?'solicitud':null;
 function isActa(name){const s=norm(name);return /(?:^|[^a-z])(actas?|reunion(?:es)?)(?=$|[^a-z])/.test(s)}
 // Solo señales financieras explícitas; no deduce contenido ni reasigna correcciones manuales.
 function finance(path){const parts=path.map(norm),dirs=parts.slice(0,-1),file=(parts.at(-1)||'').replace(/[_-]+/g,' ');const dir=dirs.join(' / ').replace(/[_-]+/g,' '),joined=dir+' / '+file;
  if(/\b(facturas?|compras?|tickets?|gastos?|recibos?)\b/.test(joined))return 'compras';
  if(/\b(presupuestos?|balance economico|presupuesto anual)\b/.test(joined))return 'presupuestos';
  if(/\b(extractos? bancarios?|cuentas? bancarias?|movimientos? bancarios?|transferencias?|conciliacion bancaria|balances?)\b/.test(joined))return 'cuentas';
  if(/\b(proveedores?)\b/.test(joined))return 'recursos';
  if(/\b(tesoreria|contabilidad|finanzas|economia)\b/.test(dir))return 'economia';
  return null;
 }
 function classify(path){
  const parts=path.map(norm), dirs=parts.slice(0,-1), file=parts.at(-1)||'', all=parts.join(' / ');
  const folder=(r)=>dirs.some(x=>has(x,r));
  if(isActa(file))return 'asambleas';
  // La ruta concreta es más fiable que una palabra suelta del nombre.
  if(folder(/cursos? .*felgtbi/))return 'cursos-felgtbi';
  if(folder(/escuela de otono.*salud|curso salud sexual.*escuela/))return 'escuela-salud';
  if(folder(/materiales charlas|charlas.*\beso\b|charlas diversidad/))return 'charlas';
  if(folder(/consejo federal/))return 'consejos';
  if(folder(/felgtbi.*(avales|ingreso)/))return 'felgtbi';
  if(folder(/materiales de difusion/)){
   if(has(all,/pegatinas|folletos?|codigos? qr/))return 'folletos';
   if(has(all,/cartel/))return 'carteles';
   return 'identidad';
  }
  if(folder(/pruebas vih|red ?covih/))return 'pruebas';
  if(folder(/previhene/))return 'previhene';
  if(folder(/subvenci/)){
   let year;for(const part of [...dirs].reverse()){const m=part.match(/\b(2015|2019|2021|2024|2025|2026)\b/);if(m){year=m[1];break}}
   if(!year){const m=file.match(/\b(2015|2019|2021|2024|2025|2026)\b/);if(m)year=m[1]}
   if(!year)return 'sub-otras';
   let kind=null;for(const part of [...dirs].reverse()){kind=stage(part);if(kind)break}if(!kind)kind=stage(file);
   return 'sub-'+year+(kind?'-'+kind:'');
  }
  if(folder(/documentos fundacionales/)){
   if(has(all,/inscripci|registro|certificado|\bnif\b|\bcif\b/))return 'registro';
   if(has(all,/estatutos?|reglamento/))return 'estatutos';
   return 'gobierno';
  }
  if(folder(/cambios? (comision ejecutiva|coordinadora)/))return 'ejecutiva';
  if(folder(/carne.*soci/))return 'carnes';
  if(folder(/solicitud.*sede social/))return 'sede-social';
  if(folder(/solicitudes? concentraciones?/))return 'permisos';
  if(folder(/palencia queerida/))return 'queerida';
  if(folder(/manifiestos?|17.d.*documental/))return 'campanas';
  if(folder(/^dsgf\b/))return 'guias';
  if(folder(/^siluetas\b/))return 'siluetas';
  if(folder(/^imprimir\b|material para imprenta/))return 'impresion';
  if(folder(/orgullo/)){
   let year;for(const part of [...dirs].reverse()){const m=part.match(/\b(2023|2024|2025)\b/);if(m){year=m[1];break}}
   return year?'orgullo-'+year:'orgullo';
  }
  if(folder(/^proyectos?$/))return 'otros-proyectos';
  // En los demás casos, reconocer señales financieras antes de dejar sin clasificar.
  const treasury=finance(path);if(treasury)return treasury;
  // Sin una carpeta reconocible, solo se usan señales precisas; lo dudoso se revisa.
  if(has(all,/solicitud sede social/))return 'sede-social';
  if(has(all,/instancias? genericas? ayuntamiento|solicitudes? ayuntamiento/))return 'registro';
  if(has(all,/protocolos? secretaria/))return 'altas';
  if(has(all,/politica de privacidad|proteccion de datos/))return 'privacidad';
  if(has(file,/\bestatutos?\b|reglamento interno/))return 'estatutos';
  if(has(file,/\bmanifiesto\b/))return 'campanas';
  return 'pendientes';
 }
 return {classify,finance,isActa};
})();
