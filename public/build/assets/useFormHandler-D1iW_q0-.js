import{c as s}from"./createLucideIcon-BfbIlJq8.js";import{j as o}from"./app-Bs-JTrqW.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const u=s("MonitorUpIcon",[["path",{d:"m9 10 3-3 3 3",key:"11gsxs"}],["path",{d:"M12 13V7",key:"h0r20n"}],["rect",{width:"20",height:"14",x:"2",y:"3",rx:"2",key:"48i651"}],["path",{d:"M12 17v4",key:"1riwvh"}],["path",{d:"M8 21h8",key:"1ev6f3"}]]);function h(){return{insertFormData:async(n,a)=>{const e=new FormData;for(const t in a)a[t]!==null&&a[t]!==void 0&&(a[t]instanceof File,e.append(t,a[t]));try{return(await o.post(n,e,{headers:{"Content-Type":"multipart/form-data"}})).data}catch(t){throw console.error("Form submission error:",t),t}},updateFormData:async(n,a)=>{const e=new FormData;for(const r in a)a[r]!==null&&a[r]!==void 0&&e.append(r,a[r]);return(await o.put(n,e,{headers:{"Content-Type":"multipart/form-data"}})).data}}}export{u as M,h as u};
