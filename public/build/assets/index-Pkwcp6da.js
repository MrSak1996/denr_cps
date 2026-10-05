import{c as w}from"./createLucideIcon-CmDjJqu7.js";import{c as S}from"./index-alax3iod.js";import{a3 as B,cp as D,cu as p,ar as E,z as b,A as I,p as y,e as a,w as m,b as d,x as c,L as i,f as v,a4 as x,B as K,K as l,F as R,h as k,n as P,t as A,y as V}from"./app-HSneDvZ5.js";import{s as $,a as M}from"./index-BlZWnRYa.js";import{s as O}from"./index-DVf77Sih.js";import{F}from"./index-C1NvlgaR.js";import{u as z,b as j}from"./index-dluyTJtU.js";import{s as Z}from"./index-Dh4WYSod.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const se=w("EllipsisVerticalIcon",[["circle",{cx:"12",cy:"12",r:"1",key:"41hilf"}],["circle",{cx:"12",cy:"5",r:"1",key:"gxeob9"}],["circle",{cx:"12",cy:"19",r:"1",key:"lyex9k"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const le=w("FileSearchIcon",[["path",{d:"M14 2v4a2 2 0 0 0 2 2h4",key:"tnqrlb"}],["path",{d:"M4.268 21a2 2 0 0 0 1.727 1H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v3",key:"ms7g94"}],["path",{d:"m9 18-1.5-1.5",key:"1j6qii"}],["circle",{cx:"5",cy:"14",r:"3",key:"ufru5t"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const de=w("MessageSquareIcon",[["path",{d:"M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z",key:"1lielz"}]]);var T=`
    .p-drawer {
        display: flex;
        flex-direction: column;
        transform: translate3d(0px, 0px, 0px);
        position: relative;
        transition: transform 0.3s;
        background: dt('drawer.background');
        color: dt('drawer.color');
        border: 1px solid dt('drawer.border.color');
        box-shadow: dt('drawer.shadow');
    }

    .p-drawer-content {
        overflow-y: auto;
        flex-grow: 1;
        padding: dt('drawer.content.padding');
    }

    .p-drawer-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        padding: dt('drawer.header.padding');
    }

    .p-drawer-footer {
        padding: dt('drawer.footer.padding');
    }

    .p-drawer-title {
        font-weight: dt('drawer.title.font.weight');
        font-size: dt('drawer.title.font.size');
    }

    .p-drawer-full .p-drawer {
        transition: none;
        transform: none;
        width: 100vw !important;
        height: 100vh !important;
        max-height: 100%;
        top: 0px !important;
        left: 0px !important;
        border-width: 1px;
    }

    .p-drawer-left .p-drawer-enter-from,
    .p-drawer-left .p-drawer-leave-to {
        transform: translateX(-100%);
    }

    .p-drawer-right .p-drawer-enter-from,
    .p-drawer-right .p-drawer-leave-to {
        transform: translateX(100%);
    }

    .p-drawer-top .p-drawer-enter-from,
    .p-drawer-top .p-drawer-leave-to {
        transform: translateY(-100%);
    }

    .p-drawer-bottom .p-drawer-enter-from,
    .p-drawer-bottom .p-drawer-leave-to {
        transform: translateY(100%);
    }

    .p-drawer-full .p-drawer-enter-from,
    .p-drawer-full .p-drawer-leave-to {
        opacity: 0;
    }

    .p-drawer-full .p-drawer-enter-active,
    .p-drawer-full .p-drawer-leave-active {
        transition: opacity 400ms cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .p-drawer-left .p-drawer {
        width: 20rem;
        height: 100%;
        border-inline-end-width: 1px;
    }

    .p-drawer-right .p-drawer {
        width: 20rem;
        height: 100%;
        border-inline-start-width: 1px;
    }

    .p-drawer-top .p-drawer {
        height: 10rem;
        width: 100%;
        border-block-end-width: 1px;
    }

    .p-drawer-bottom .p-drawer {
        height: 10rem;
        width: 100%;
        border-block-start-width: 1px;
    }

    .p-drawer-left .p-drawer-content,
    .p-drawer-right .p-drawer-content,
    .p-drawer-top .p-drawer-content,
    .p-drawer-bottom .p-drawer-content {
        width: 100%;
        height: 100%;
    }

    .p-drawer-open {
        display: flex;
    }

    .p-drawer-mask:dir(rtl) {
        flex-direction: row-reverse;
    }
`,q={mask:function(t){var n=t.position,o=t.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:n==="left"?"flex-start":n==="right"?"flex-end":"center",alignItems:n==="top"?"flex-start":n==="bottom"?"flex-end":"center",pointerEvents:o?"auto":"none"}},root:{pointerEvents:"auto"}},N={mask:function(t){var n=t.instance,o=t.props,s=["left","right","top","bottom"],r=s.find(function(f){return f===o.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter":o.modal,"p-drawer-open":n.containerVisible,"p-drawer-full":n.fullScreen},r?"p-drawer-".concat(r):""]},root:function(t){var n=t.instance;return["p-drawer p-component",{"p-drawer-full":n.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},H=B.extend({name:"drawer",style:T,classes:N,inlineStyles:q}),U={name:"BaseDrawer",extends:Z,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1}},style:H,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function u(e){"@babel/helpers - typeof";return u=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},u(e)}function h(e,t,n){return(t=X(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function X(e){var t=Y(e,"string");return u(t)=="symbol"?t:t+""}function Y(e,t){if(u(e)!="object"||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var o=n.call(e,t);if(u(o)!="object")return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var G={name:"Drawer",extends:U,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(t){t?this.enableDocumentSettings():this.disableDocumentSettings()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&p.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&p.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&E(this.mask,"p-overlay-mask-leave"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&p.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(t){this.dismissable&&this.modal&&this.mask===t.target&&this.hide()},focus:function(){var t=function(s){return s&&s.querySelector("[autofocus]")},n=this.$slots.header&&t(this.headerContainer);n||(n=this.$slots.default&&t(this.container),n||(n=this.$slots.footer&&t(this.footerContainer),n||(n=this.closeButton))),n&&D(n)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&j()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&z()},onKeydown:function(t){t.code==="Escape"&&this.hide()},containerRef:function(t){this.container=t},maskRef:function(t){this.mask=t},contentRef:function(t){this.content=t},headerContainerRef:function(t){this.headerContainer=t},footerContainerRef:function(t){this.footerContainer=t},closeButtonRef:function(t){this.closeButton=t?t.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var t=this;this.outsideClickListener||(this.outsideClickListener=function(n){t.isOutsideClicked(n)&&t.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(t){return this.container&&!this.container.contains(t.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return S(h(h(h({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:F},components:{Button:O,Portal:M,TimesIcon:$}},J=["data-p"],Q=["aria-modal","data-p"];function W(e,t,n,o,s,r){var f=b("Button"),g=b("Portal"),L=I("focustrap");return a(),y(g,null,{default:m(function(){return[s.containerVisible?(a(),d("div",i({key:0,ref:r.maskRef,onMousedown:t[0]||(t[0]=function(){return r.onMaskClick&&r.onMaskClick.apply(r,arguments)}),class:e.cx("mask"),style:e.sx("mask",!0,{position:e.position,modal:e.modal}),"data-p":r.dataP},e.ptm("mask")),[v(x,i({name:"p-drawer",onEnter:r.onEnter,onAfterEnter:r.onAfterEnter,onBeforeLeave:r.onBeforeLeave,onLeave:r.onLeave,onAfterLeave:r.onAfterLeave,appear:""},e.ptm("transition")),{default:m(function(){return[e.visible?K((a(),d("div",i({key:0,ref:r.containerRef,class:e.cx("root"),style:e.sx("root"),role:"complementary","aria-modal":e.modal,"data-p":r.dataP},e.ptmi("root")),[e.$slots.container?l(e.$slots,"container",{key:0,closeCallback:r.hide}):(a(),d(R,{key:1},[k("div",i({ref:r.headerContainerRef,class:e.cx("header")},e.ptm("header")),[l(e.$slots,"header",{class:P(e.cx("title"))},function(){return[e.header?(a(),d("div",i({key:0,class:e.cx("title")},e.ptm("title")),A(e.header),17)):c("",!0)]}),e.showCloseIcon?l(e.$slots,"closebutton",{key:0,closeCallback:r.hide},function(){return[v(f,i({ref:r.closeButtonRef,type:"button",class:e.cx("pcCloseButton"),"aria-label":r.closeAriaLabel,unstyled:e.unstyled,onClick:r.hide},e.closeButtonProps,{pt:e.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:m(function(C){return[l(e.$slots,"closeicon",{},function(){return[(a(),y(V(e.closeIcon?"span":"TimesIcon"),i({class:[e.closeIcon,C.class]},e.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):c("",!0)],16),k("div",i({ref:r.contentRef,class:e.cx("content")},e.ptm("content")),[l(e.$slots,"default")],16),e.$slots.footer?(a(),d("div",i({key:0,ref:r.footerContainerRef,class:e.cx("footer")},e.ptm("footer")),[l(e.$slots,"footer")],16)):c("",!0)],64))],16,Q)),[[L]]):c("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,J)):c("",!0)]}),_:3})}G.render=W;export{se as E,le as F,de as M,G as s};
