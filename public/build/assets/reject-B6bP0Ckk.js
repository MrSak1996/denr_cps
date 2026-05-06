import{s as d}from"./index-CJYJItea.js";import{a3 as i,b as s,e as a,x as o,h as n,L as t,K as r}from"./app-BRcEDJ1c.js";var l=`
    .p-card {
        background: dt('card.background');
        color: dt('card.color');
        box-shadow: dt('card.shadow');
        border-radius: dt('card.border.radius');
        display: flex;
        flex-direction: column;
    }

    .p-card-caption {
        display: flex;
        flex-direction: column;
        gap: dt('card.caption.gap');
    }

    .p-card-body {
        padding: dt('card.body.padding');
        display: flex;
        flex-direction: column;
        gap: dt('card.body.gap');
    }

    .p-card-title {
        font-size: dt('card.title.font.size');
        font-weight: dt('card.title.font.weight');
    }

    .p-card-subtitle {
        color: dt('card.subtitle.color');
    }
`,c={root:"p-card p-component",header:"p-card-header",body:"p-card-body",caption:"p-card-caption",title:"p-card-title",subtitle:"p-card-subtitle",content:"p-card-content",footer:"p-card-footer"},p=i.extend({name:"card",style:l,classes:c}),u={name:"BaseCard",extends:d,style:p,provide:function(){return{$pcCard:this,$parentInstance:this}}},b={name:"Card",extends:u,inheritAttrs:!1};function f(e,m,y,v,$,g){return a(),s("div",t({class:e.cx("root")},e.ptmi("root")),[e.$slots.header?(a(),s("div",t({key:0,class:e.cx("header")},e.ptm("header")),[r(e.$slots,"header")],16)):o("",!0),n("div",t({class:e.cx("body")},e.ptm("body")),[e.$slots.title||e.$slots.subtitle?(a(),s("div",t({key:0,class:e.cx("caption")},e.ptm("caption")),[e.$slots.title?(a(),s("div",t({key:0,class:e.cx("title")},e.ptm("title")),[r(e.$slots,"title")],16)):o("",!0),e.$slots.subtitle?(a(),s("div",t({key:1,class:e.cx("subtitle")},e.ptm("subtitle")),[r(e.$slots,"subtitle")],16)):o("",!0)],16)):o("",!0),n("div",t({class:e.cx("content")},e.ptm("content")),[r(e.$slots,"content")],16),e.$slots.footer?(a(),s("div",t({key:1,class:e.cx("footer")},e.ptm("footer")),[r(e.$slots,"footer")],16)):o("",!0)],16)],16)}b.render=f;const w="/build/assets/application-3TwtrZJ0.png",C="/build/assets/review-tykee6Ya.png",B="/build/assets/approved-D-6WwDfF.png",V="/build/assets/reject-CoVPtsfV.png";export{B as a,V as b,C as r,b as s,w as t};
