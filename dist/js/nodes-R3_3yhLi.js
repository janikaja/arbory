import{C as r}from"./js.cookie-CFT9JG7m.js";class h{constructor(e,t={},s){this.id=e,this.contents=t,this.store=s}getContents(){return this.contents}isCollapsed(){return this.contents}setCollapsed(e){this.contents=e,this.save()}save(){this.store&&this.store.saveItem(this)}}const a={};class c{constructor(e){this.storeName=e,a[e]=this}getStored(){const e=r.getJSON(this.storeName);return typeof e!="object"?(this.save({}),{}):e}get(e){let t=this.getStored();return typeof t[e]>"u"&&(t[e]=!0,this.save(t)),new h(e,t[e],this)}saveItem(e){let t=this.getStored();t[e.id]=e.getContents(),this.save(t)}save(e){r.set(this.storeName,e)}static getInstance(e){return a[e]}}class i{constructor(e,t){this.element=e,this.store=t,this.id=e.data("id"),this.level=e.data("level")}getStorage(){return this.store?this.store.get(this.id):null}makeSortable(e){return this.element.parent().sortable(e)}toggleChildVisibility(){let e=this.getStorage();e&&e.setCollapsed(!e.isCollapsed()),this.isCollapsed()?this.element.removeClass("collapsed"):this.element.addClass("collapsed")}isCollapsed(){return this.element.hasClass("collapsed")}getParent(){const e="li[data-level="+--this.level+"]";return new i(this.element.closest(e),this.store)}getLeftSibling(){return new i(this.element.prev("li[data-id]"),this.store)}getRightSibling(){return new i(this.element.next("li[data-id]"),this.store)}}class n{constructor(e,t="",s=null){this.repositionUrl=t,this.collection=jQuery(e),this.store=s?new c(s):null,this.initialize()}initialize(){this.collection.data("tree",this),jQuery("body").on("click",".dialog .node-cell label",this.onDialogLabelClick.bind(this)),jQuery(document).ready(()=>{this.collection.find("ul[data-level] > li").each((t,s)=>{let o=new i(jQuery(s),this.store);o.element.on("click","> .collapser-cell > .collapser",()=>o.toggleChildVisibility()),o.makeSortable({items:"> li",stop:(u,d)=>{o=new i(d.item,this.store),this.send(o)}})})})}onDialogLabelClick(e){jQuery(".dialog .node-cell label").removeClass("selected"),jQuery(e.target).addClass("selected")}send(e){jQuery.post(this.repositionUrl,{_token:n.getToken(),id:e.id,toLeftId:e.getLeftSibling().id,toRightId:e.getRightSibling().id})}static getToken(){return jQuery("[name=_token]:first").val()}}const g="nodes";jQuery(document).ready(()=>{const l=jQuery("body.controller-nodes .collection");new n(l,l.data("reposition-url")||window.NODE_REPOSITION_BASEPATH,l.data("store-name")||g)});