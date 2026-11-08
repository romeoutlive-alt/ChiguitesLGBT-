from pathlib import Path
import re
p=Path('.');base='/chiguiteslgbt-'
for f in p.rglob('*.html'):
 s=f.read_text()
 s=re.sub(r'((?:href|src|action)=["\'])(/(?!/)[^"\']*)',lambda m:m.group(1)+base+m.group(2),s)
 # The embedded TanStack SSR manifest also refers to the root asset directory.
 s=s.replace('"/assets/','"'+base+'/assets/')
 f.write_text(s)
for f in (p/'assets').glob('*.js'):
 s=f.read_text()
 for name in ('images','fonts','previews','api'):
  s=s.replace('`/'+name+'/', '`'+base+'/'+name+'/').replace('"/'+name+'/', '"'+base+'/'+name+'/').replace("'/"+name+'/', "'"+base+'/'+name+'/')
 if f.name.startswith('index-Dyt'):
  assert s.count('e.update({basepath:``,serializationAdapters:t})')==1
  s=s.replace('e.update({basepath:``,serializationAdapters:t})','e.update({basepath:`/chiguiteslgbt-`,serializationAdapters:t})')
  assert s.count('Ap=function(e){return`/`+e}')==1
  s=s.replace('Ap=function(e){return`/`+e}','Ap=function(e){return`/chiguiteslgbt-/`+e}')
 f.write_text(s)
f=p/'assets/index-j_oN-MV6.css';s=f.read_text().replace('url(/images/','url('+base+'/images/');f.write_text(s)

for f in p.rglob('*.html'):
 s=f.read_text();s=s.replace('<head>','<head><link rel="icon" href="/chiguiteslgbt-/favicon.ico"/>',1);f.write_text(s)

from pathlib import Path
p=Path('.'); url='https://app.notion.com/p/412eb5cbef4880e0913bcfc83e59bec6'
label='Sede interna · Administración'
aria='Sede interna para administración en Notion (se abre en otra pestaña)'
for f in p.rglob('*.html'):
 s=f.read_text(); old='</footer>'; assert s.count(old)==1
 new=f'<a class="admin-access" href="{url}" target="_blank" rel="noopener noreferrer" aria-label="{aria}">🔒 {label}</a></footer>'
 f.write_text(s.replace(old,new))
f=p/'assets/index-DytNzpmY.js';s=f.read_text(); old='(0,V.jsx)(Lc,{to:`/informacion`,children:`Nuestra historia`})]})'; assert s.count(old)==1
new=f'(0,V.jsx)(Lc,{{to:`/informacion`,children:`Nuestra historia`}}),(0,V.jsx)(`a`,{{className:`admin-access`,href:`{url}`,target:`_blank`,rel:`noopener noreferrer`,"aria-label":`{aria}`,children:`🔒 {label}`}})]}})'
f.write_text(s.replace(old,new))
f=p/'assets/index-j_oN-MV6.css';s=f.read_text();s+='\n/* Acceso interno: el control de acceso lo gestiona Notion. */\n.site-footer{flex-wrap:wrap}.site-footer>a.admin-access{min-height:44px;padding:10px 14px;border:1px solid #6047a0;border-radius:8px;color:#4c317e;font-size:14px;font-weight:650;line-height:1.35;text-decoration:none}.site-footer>a.admin-access:hover{background:#eee6f2;color:#39235f;text-decoration:none}.site-footer>a.admin-access:focus-visible{outline:3px solid #4c317e;outline-offset:3px}\n';f.write_text(s)


for f in p.rglob('*.html'):
 s=f.read_text(); x=s.index('<a class="admin-access"');y=s.index('</a>',x)+4; a=s[x:y];s=s[:x]+s[y:];start=s.index('<footer class="site-footer">');end=s.index('</a>',start)+4;s=s[:end]+a+s[end:];f.write_text(s)
f=p/'assets/index-DytNzpmY.js';s=f.read_text();i=s.index('(0,V.jsx)(`a`,{className:`admin-access`');j=s.index('})',i)+2;a=s[i:j];s=s[:i-1]+s[j:];prefix='(0,V.jsx)(`p`,{children:`Desde Palencia. Con orgullo y contigo.`})';assert s.count(prefix)==1;s=s.replace(prefix,a+','+prefix);f.write_text(s)
f=p/'assets/index-j_oN-MV6.css';f.write_text(f.read_text()+"\n@media (max-width: 640px){.site-footer>a.admin-access{grid-column:1/-1;justify-self:start}}\n@media (min-width: 901px){.site-footer{padding-right:max(6%,220px)}}\n@media (max-width: 640px){.site-footer{grid-template-columns:1fr;gap:16px}.site-footer>a.admin-access{grid-column:auto}.site-footer p{grid-column:auto;max-width:190px}}\n")
