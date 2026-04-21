import{c as r}from"./createLucideIcon-B23wGqrj.js";import{s as t}from"./index-B4ROSit8.js";import{B as o,C as s,b as l,e as d,x as i,f as p,v as a}from"./app-BdHy0PNN.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const H=r("HistoryIcon",[["path",{d:"M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8",key:"1357e3"}],["path",{d:"M3 3v5h5",key:"1xhq8a"}],["path",{d:"M12 7v5l4 2",key:"1fdv2h"}]]);var v=`
    .p-overlaybadge {
        position: relative;
    }

    .p-overlaybadge .p-badge {
        position: absolute;
        inset-block-start: 0;
        inset-inline-end: 0;
        transform: translate(50%, -50%);
        transform-origin: 100% 0;
        margin: 0;
        outline-width: dt('overlaybadge.outline.width');
        outline-style: solid;
        outline-color: dt('overlaybadge.outline.color');
    }

    .p-overlaybadge .p-badge:dir(rtl) {
        transform: translate(-50%, -50%);
    }
`,c={root:"p-overlaybadge"},y=o.extend({name:"overlaybadge",style:v,classes:c}),g={name:"OverlayBadge",extends:t,style:y,provide:function(){return{$pcOverlayBadge:this,$parentInstance:this}}},m={name:"OverlayBadge",extends:g,inheritAttrs:!1,components:{Badge:t}};function u(e,b,f,h,B,$){var n=s("Badge");return d(),l("div",a({class:e.cx("root")},e.ptmi("root")),[i(e.$slots,"default"),p(n,a(e.$props,{pt:e.ptm("pcBadge")}),null,16,["pt"])],16)}m.render=u;export{H,m as s};
