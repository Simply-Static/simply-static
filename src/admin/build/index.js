/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@emotion/is-prop-valid/dist/emotion-is-prop-valid.esm.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/@emotion/is-prop-valid/dist/emotion-is-prop-valid.esm.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isPropValid)
/* harmony export */ });
/* harmony import */ var _emotion_memoize__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @emotion/memoize */ "./node_modules/@emotion/memoize/dist/emotion-memoize.esm.js");


var reactPropsRegex = /^((children|dangerouslySetInnerHTML|key|ref|autoFocus|defaultValue|defaultChecked|innerHTML|suppressContentEditableWarning|suppressHydrationWarning|valueLink|abbr|accept|acceptCharset|accessKey|action|allow|allowUserMedia|allowPaymentRequest|allowFullScreen|allowTransparency|alt|async|autoComplete|autoPlay|capture|cellPadding|cellSpacing|challenge|charSet|checked|cite|classID|className|cols|colSpan|content|contentEditable|contextMenu|controls|controlsList|coords|crossOrigin|data|dateTime|decoding|default|defer|dir|disabled|disablePictureInPicture|disableRemotePlayback|download|draggable|encType|enterKeyHint|form|formAction|formEncType|formMethod|formNoValidate|formTarget|frameBorder|headers|height|hidden|high|href|hrefLang|htmlFor|httpEquiv|id|inputMode|integrity|is|keyParams|keyType|kind|label|lang|list|loading|loop|low|marginHeight|marginWidth|max|maxLength|media|mediaGroup|method|min|minLength|multiple|muted|name|nonce|noValidate|open|optimum|pattern|placeholder|playsInline|poster|preload|profile|radioGroup|readOnly|referrerPolicy|rel|required|reversed|role|rows|rowSpan|sandbox|scope|scoped|scrolling|seamless|selected|shape|size|sizes|slot|span|spellCheck|src|srcDoc|srcLang|srcSet|start|step|style|summary|tabIndex|target|title|translate|type|useMap|value|width|wmode|wrap|about|datatype|inlist|prefix|property|resource|typeof|vocab|autoCapitalize|autoCorrect|autoSave|color|incremental|fallback|inert|itemProp|itemScope|itemType|itemID|itemRef|on|option|results|security|unselectable|accentHeight|accumulate|additive|alignmentBaseline|allowReorder|alphabetic|amplitude|arabicForm|ascent|attributeName|attributeType|autoReverse|azimuth|baseFrequency|baselineShift|baseProfile|bbox|begin|bias|by|calcMode|capHeight|clip|clipPathUnits|clipPath|clipRule|colorInterpolation|colorInterpolationFilters|colorProfile|colorRendering|contentScriptType|contentStyleType|cursor|cx|cy|d|decelerate|descent|diffuseConstant|direction|display|divisor|dominantBaseline|dur|dx|dy|edgeMode|elevation|enableBackground|end|exponent|externalResourcesRequired|fill|fillOpacity|fillRule|filter|filterRes|filterUnits|floodColor|floodOpacity|focusable|fontFamily|fontSize|fontSizeAdjust|fontStretch|fontStyle|fontVariant|fontWeight|format|from|fr|fx|fy|g1|g2|glyphName|glyphOrientationHorizontal|glyphOrientationVertical|glyphRef|gradientTransform|gradientUnits|hanging|horizAdvX|horizOriginX|ideographic|imageRendering|in|in2|intercept|k|k1|k2|k3|k4|kernelMatrix|kernelUnitLength|kerning|keyPoints|keySplines|keyTimes|lengthAdjust|letterSpacing|lightingColor|limitingConeAngle|local|markerEnd|markerMid|markerStart|markerHeight|markerUnits|markerWidth|mask|maskContentUnits|maskUnits|mathematical|mode|numOctaves|offset|opacity|operator|order|orient|orientation|origin|overflow|overlinePosition|overlineThickness|panose1|paintOrder|pathLength|patternContentUnits|patternTransform|patternUnits|pointerEvents|points|pointsAtX|pointsAtY|pointsAtZ|preserveAlpha|preserveAspectRatio|primitiveUnits|r|radius|refX|refY|renderingIntent|repeatCount|repeatDur|requiredExtensions|requiredFeatures|restart|result|rotate|rx|ry|scale|seed|shapeRendering|slope|spacing|specularConstant|specularExponent|speed|spreadMethod|startOffset|stdDeviation|stemh|stemv|stitchTiles|stopColor|stopOpacity|strikethroughPosition|strikethroughThickness|string|stroke|strokeDasharray|strokeDashoffset|strokeLinecap|strokeLinejoin|strokeMiterlimit|strokeOpacity|strokeWidth|surfaceScale|systemLanguage|tableValues|targetX|targetY|textAnchor|textDecoration|textRendering|textLength|to|transform|u1|u2|underlinePosition|underlineThickness|unicode|unicodeBidi|unicodeRange|unitsPerEm|vAlphabetic|vHanging|vIdeographic|vMathematical|values|vectorEffect|version|vertAdvY|vertOriginX|vertOriginY|viewBox|viewTarget|visibility|widths|wordSpacing|writingMode|x|xHeight|x1|x2|xChannelSelector|xlinkActuate|xlinkArcrole|xlinkHref|xlinkRole|xlinkShow|xlinkTitle|xlinkType|xmlBase|xmlns|xmlnsXlink|xmlLang|xmlSpace|y|y1|y2|yChannelSelector|z|zoomAndPan|for|class|autofocus)|(([Dd][Aa][Tt][Aa]|[Aa][Rr][Ii][Aa]|x)-.*))$/; // https://esbench.com/bench/5bfee68a4cd7e6009ef61d23

var isPropValid = /* #__PURE__ */(0,_emotion_memoize__WEBPACK_IMPORTED_MODULE_0__["default"])(function (prop) {
  return reactPropsRegex.test(prop) || prop.charCodeAt(0) === 111
  /* o */
  && prop.charCodeAt(1) === 110
  /* n */
  && prop.charCodeAt(2) < 91;
}
/* Z+1 */
);




/***/ }),

/***/ "./node_modules/@emotion/memoize/dist/emotion-memoize.esm.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@emotion/memoize/dist/emotion-memoize.esm.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ memoize)
/* harmony export */ });
function memoize(fn) {
  var cache = Object.create(null);
  return function (arg) {
    if (cache[arg] === undefined) cache[arg] = fn(arg);
    return cache[arg];
  };
}




/***/ }),

/***/ "./node_modules/@emotion/unitless/dist/emotion-unitless.esm.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@emotion/unitless/dist/emotion-unitless.esm.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ unitlessKeys)
/* harmony export */ });
var unitlessKeys = {
  animationIterationCount: 1,
  aspectRatio: 1,
  borderImageOutset: 1,
  borderImageSlice: 1,
  borderImageWidth: 1,
  boxFlex: 1,
  boxFlexGroup: 1,
  boxOrdinalGroup: 1,
  columnCount: 1,
  columns: 1,
  flex: 1,
  flexGrow: 1,
  flexPositive: 1,
  flexShrink: 1,
  flexNegative: 1,
  flexOrder: 1,
  gridRow: 1,
  gridRowEnd: 1,
  gridRowSpan: 1,
  gridRowStart: 1,
  gridColumn: 1,
  gridColumnEnd: 1,
  gridColumnSpan: 1,
  gridColumnStart: 1,
  msGridRow: 1,
  msGridRowSpan: 1,
  msGridColumn: 1,
  msGridColumnSpan: 1,
  fontWeight: 1,
  lineHeight: 1,
  opacity: 1,
  order: 1,
  orphans: 1,
  tabSize: 1,
  widows: 1,
  zIndex: 1,
  zoom: 1,
  WebkitLineClamp: 1,
  // SVG-related properties
  fillOpacity: 1,
  floodOpacity: 1,
  stopOpacity: 1,
  strokeDasharray: 1,
  strokeDashoffset: 1,
  strokeMiterlimit: 1,
  strokeOpacity: 1,
  strokeWidth: 1
};




/***/ }),

/***/ "./node_modules/react-data-table-component/dist/index.cjs.js":
/*!*******************************************************************!*\
  !*** ./node_modules/react-data-table-component/dist/index.cjs.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";
Object.defineProperty(exports, "__esModule", ({value:!0}));var e=__webpack_require__(/*! react */ "react"),t=__webpack_require__(/*! styled-components */ "./node_modules/styled-components/dist/styled-components.browser.esm.js");function n(e){return e&&"object"==typeof e&&"default"in e?e:{default:e}}function o(e){if(e&&e.__esModule)return e;var t=Object.create(null);return e&&Object.keys(e).forEach((function(n){if("default"!==n){var o=Object.getOwnPropertyDescriptor(e,n);Object.defineProperty(t,n,o.get?o:{enumerable:!0,get:function(){return e[n]}})}})),t.default=e,Object.freeze(t)}var a,l=o(e),r=n(e),i=n(t);function s(e,t){return e[t]}function d(e=[],t,n=0){return[...e.slice(0,n),t,...e.slice(n)]}function c(e=[],t,n="id"){const o=e.slice(),a=s(t,n);return a?o.splice(o.findIndex((e=>s(e,n)===a)),1):o.splice(o.findIndex((e=>e===t)),1),o}function g(e){return e.map(((e,t)=>{const n=Object.assign(Object.assign({},e),{sortable:e.sortable||!!e.sortFunction||void 0});return e.id||(n.id=t+1),n}))}function u(e,t){return Math.ceil(e/t)}function p(e,t){return Math.min(e,t)}!function(e){e.ASC="asc",e.DESC="desc"}(a||(a={}));const b=()=>null;function m(e,t=[],n=[]){let o={},a=[...n];return t.length&&t.forEach((t=>{if(!t.when||"function"!=typeof t.when)throw new Error('"when" must be defined in the conditional style object and must be function');t.when(e)&&(o=t.style||{},t.classNames&&(a=[...a,...t.classNames]),"function"==typeof t.style&&(o=t.style(e)||{}))})),{conditionalStyle:o,classNames:a.join(" ")}}function f(e,t=[],n="id"){const o=s(e,n);return o?t.some((e=>s(e,n)===o)):t.some((t=>t===e))}function h(e,t){return t?e.findIndex((e=>w(e.id,t))):-1}function w(e,t){return e==t}function x(e,t){const n=!e.toggleOnSelectedRowsChange;switch(t.type){case"SELECT_ALL_ROWS":{const{keyField:n,rows:o,rowCount:a,mergeSelections:l}=t,r=!e.allSelected,i=!e.toggleOnSelectedRowsChange;if(l){const t=r?[...e.selectedRows,...o.filter((t=>!f(t,e.selectedRows,n)))]:e.selectedRows.filter((e=>!f(e,o,n)));return Object.assign(Object.assign({},e),{allSelected:r,selectedCount:t.length,selectedRows:t,toggleOnSelectedRowsChange:i})}return Object.assign(Object.assign({},e),{allSelected:r,selectedCount:r?a:0,selectedRows:r?o:[],toggleOnSelectedRowsChange:i})}case"SELECT_SINGLE_ROW":{const{keyField:o,row:a,isSelected:l,rowCount:r,singleSelect:i}=t;return i?l?Object.assign(Object.assign({},e),{selectedCount:0,allSelected:!1,selectedRows:[],toggleOnSelectedRowsChange:n}):Object.assign(Object.assign({},e),{selectedCount:1,allSelected:!1,selectedRows:[a],toggleOnSelectedRowsChange:n}):l?Object.assign(Object.assign({},e),{selectedCount:e.selectedRows.length>0?e.selectedRows.length-1:0,allSelected:!1,selectedRows:c(e.selectedRows,a,o),toggleOnSelectedRowsChange:n}):Object.assign(Object.assign({},e),{selectedCount:e.selectedRows.length+1,allSelected:e.selectedRows.length+1===r,selectedRows:d(e.selectedRows,a),toggleOnSelectedRowsChange:n})}case"SELECT_MULTIPLE_ROWS":{const{keyField:o,selectedRows:a,totalRows:l,mergeSelections:r}=t;if(r){const t=[...e.selectedRows,...a.filter((t=>!f(t,e.selectedRows,o)))];return Object.assign(Object.assign({},e),{selectedCount:t.length,allSelected:!1,selectedRows:t,toggleOnSelectedRowsChange:n})}return Object.assign(Object.assign({},e),{selectedCount:a.length,allSelected:a.length===l,selectedRows:a,toggleOnSelectedRowsChange:n})}case"CLEAR_SELECTED_ROWS":{const{selectedRowsFlag:n}=t;return Object.assign(Object.assign({},e),{allSelected:!1,selectedCount:0,selectedRows:[],selectedRowsFlag:n})}case"SORT_CHANGE":{const{sortDirection:o,selectedColumn:a,clearSelectedOnSort:l}=t;return Object.assign(Object.assign(Object.assign({},e),{selectedColumn:a,sortDirection:o,currentPage:1}),l&&{allSelected:!1,selectedCount:0,selectedRows:[],toggleOnSelectedRowsChange:n})}case"CHANGE_PAGE":{const{page:o,paginationServer:a,visibleOnly:l,persistSelectedOnPageChange:r}=t,i=a&&r,s=a&&!r||l;return Object.assign(Object.assign(Object.assign(Object.assign({},e),{currentPage:o}),i&&{allSelected:!1}),s&&{allSelected:!1,selectedCount:0,selectedRows:[],toggleOnSelectedRowsChange:n})}case"CHANGE_ROWS_PER_PAGE":{const{rowsPerPage:n,page:o}=t;return Object.assign(Object.assign({},e),{currentPage:o,rowsPerPage:n})}}}const C=t.css`
	pointer-events: none;
	opacity: 0.4;
`,y=i.default.div`
	position: relative;
	box-sizing: border-box;
	display: flex;
	flex-direction: column;
	width: 100%;
	height: 100%;
	max-width: 100%;
	${({disabled:e})=>e&&C};
	${({theme:e})=>e.table.style};
`,v=t.css`
	position: sticky;
	position: -webkit-sticky; /* Safari */
	top: 0;
	z-index: 1;
`,R=i.default.div`
	display: flex;
	width: 100%;
	${({$fixedHeader:e})=>e&&v};
	${({theme:e})=>e.head.style};
`,S=i.default.div`
	display: flex;
	align-items: stretch;
	width: 100%;
	${({theme:e})=>e.headRow.style};
	${({$dense:e,theme:t})=>e&&t.headRow.denseStyle};
`,E=(e,...n)=>t.css`
		@media screen and (max-width: ${599}px) {
			${t.css(e,...n)}
		}
	`,O=(e,...n)=>t.css`
		@media screen and (max-width: ${959}px) {
			${t.css(e,...n)}
		}
	`,$=(e,...n)=>t.css`
		@media screen and (max-width: ${1280}px) {
			${t.css(e,...n)}
		}
	`,P=e=>(n,...o)=>t.css`
			@media screen and (max-width: ${e}px) {
				${t.css(n,...o)}
			}
		`,k=i.default.div`
	position: relative;
	display: flex;
	align-items: center;
	box-sizing: border-box;
	line-height: normal;
	${({theme:e,$headCell:t})=>e[t?"headCells":"cells"].style};
	${({$noPadding:e})=>e&&"padding: 0"};
`,D=i.default(k)`
	flex-grow: ${({button:e,grow:t})=>0===t||e?0:t||1};
	flex-shrink: 0;
	flex-basis: 0;
	max-width: ${({maxWidth:e})=>e||"100%"};
	min-width: ${({minWidth:e})=>e||"100px"};
	${({width:e})=>e&&t.css`
			min-width: ${e};
			max-width: ${e};
		`};
	${({right:e})=>e&&"justify-content: flex-end"};
	${({button:e,center:t})=>(t||e)&&"justify-content: center"};
	${({compact:e,button:t})=>(e||t)&&"padding: 0"};

	/* handle hiding cells */
	${({hide:e})=>e&&"sm"===e&&E`
    display: none;
  `};
	${({hide:e})=>e&&"md"===e&&O`
    display: none;
  `};
	${({hide:e})=>e&&"lg"===e&&$`
    display: none;
  `};
	${({hide:e})=>e&&Number.isInteger(e)&&P(e)`
    display: none;
  `};
`,H=t.css`
	div:first-child {
		white-space: ${({$wrapCell:e})=>e?"normal":"nowrap"};
		overflow: ${({$allowOverflow:e})=>e?"visible":"hidden"};
		text-overflow: ellipsis;
	}
`,j=i.default(D).attrs((e=>({style:e.style})))`
	${({$renderAsCell:e})=>!e&&H};
	${({theme:e,$isDragging:t})=>t&&e.cells.draggingStyle};
	${({$cellStyle:e})=>e};
`;var F=l.memo((function({id:e,column:t,row:n,rowIndex:o,dataTag:a,isDragging:r,onDragStart:i,onDragOver:s,onDragEnd:d,onDragEnter:c,onDragLeave:g}){const{conditionalStyle:u,classNames:p}=m(n,t.conditionalCellStyles,["rdt_TableCell"]);return l.createElement(j,{id:e,"data-column-id":t.id,role:"cell",className:p,"data-tag":a,$cellStyle:t.style,$renderAsCell:!!t.cell,$allowOverflow:t.allowOverflow,button:t.button,center:t.center,compact:t.compact,grow:t.grow,hide:t.hide,maxWidth:t.maxWidth,minWidth:t.minWidth,right:t.right,width:t.width,$wrapCell:t.wrap,style:u,$isDragging:r,onDragStart:i,onDragOver:s,onDragEnd:d,onDragEnter:c,onDragLeave:g},!t.cell&&l.createElement("div",{"data-tag":a},function(e,t,n,o){return t?n&&"function"==typeof n?n(e,o):t(e,o):null}(n,t.selector,t.format,o)),t.cell&&t.cell(n,o,t,e))}));const T="input";var I=l.memo((function({name:e,component:t=T,componentOptions:n={style:{}},indeterminate:o=!1,checked:a=!1,disabled:r=!1,onClick:i=b}){const s=t,d=s!==T?n.style:(e=>Object.assign(Object.assign({fontSize:"18px"},!e&&{cursor:"pointer"}),{padding:0,marginTop:"1px",verticalAlign:"middle",position:"relative"}))(r),c=l.useMemo((()=>function(e,...t){let n;return Object.keys(e).map((t=>e[t])).forEach(((o,a)=>{const l=e;"function"==typeof o&&(n=Object.assign(Object.assign({},l),{[Object.keys(e)[a]]:o(...t)}))})),n||e}(n,o)),[n,o]);return l.createElement(s,Object.assign({type:"checkbox",ref:e=>{e&&(e.indeterminate=o)},style:d,onClick:r?b:i,name:e,"aria-label":e,checked:a,disabled:r},c,{onChange:b}))}));const M=i.default(k)`
	flex: 0 0 48px;
	min-width: 48px;
	justify-content: center;
	align-items: center;
	user-select: none;
	white-space: nowrap;
`;function A({name:e,keyField:t,row:n,rowCount:o,selected:a,selectableRowsComponent:r,selectableRowsComponentProps:i,selectableRowsSingle:s,selectableRowDisabled:d,onSelectedRow:c}){const g=!(!d||!d(n));return l.createElement(M,{onClick:e=>e.stopPropagation(),className:"rdt_TableCell",$noPadding:!0},l.createElement(I,{name:e,component:r,componentOptions:i,checked:a,"aria-checked":a,onClick:()=>{c({type:"SELECT_SINGLE_ROW",row:n,isSelected:a,keyField:t,rowCount:o,singleSelect:s})},disabled:g}))}const L=i.default.button`
	display: inline-flex;
	align-items: center;
	user-select: none;
	white-space: nowrap;
	border: none;
	background-color: transparent;
	${({theme:e})=>e.expanderButton.style};
`;function _({disabled:e=!1,expanded:t=!1,expandableIcon:n,id:o,row:a,onToggled:r}){const i=t?n.expanded:n.collapsed;return l.createElement(L,{"aria-disabled":e,onClick:()=>r&&r(a),"data-testid":`expander-button-${o}`,disabled:e,"aria-label":t?"Collapse Row":"Expand Row",role:"button",type:"button"},i)}const z=i.default(k)`
	white-space: nowrap;
	font-weight: 400;
	min-width: 48px;
	${({theme:e})=>e.expanderCell.style};
`;function N({row:e,expanded:t=!1,expandableIcon:n,id:o,onToggled:a,disabled:r=!1}){return l.createElement(z,{onClick:e=>e.stopPropagation(),$noPadding:!0},l.createElement(_,{id:o,row:e,expanded:t,expandableIcon:n,disabled:r,onToggled:a}))}const W=i.default.div`
	width: 100%;
	box-sizing: border-box;
	${({theme:e})=>e.expanderRow.style};
	${({$extendedRowStyle:e})=>e};
`;var B=l.memo((function({data:e,ExpanderComponent:t,expanderComponentProps:n,extendedRowStyle:o,extendedClassNames:a}){const r=["rdt_ExpanderRow",...a.split(" ").filter((e=>"rdt_TableRow"!==e))].join(" ");return l.createElement(W,{className:r,$extendedRowStyle:o},l.createElement(t,Object.assign({data:e},n)))}));const G="allowRowEvents";var V,U,q;exports.Direction=void 0,(V=exports.Direction||(exports.Direction={})).LTR="ltr",V.RTL="rtl",V.AUTO="auto",exports.Alignment=void 0,(U=exports.Alignment||(exports.Alignment={})).LEFT="left",U.RIGHT="right",U.CENTER="center",exports.Media=void 0,(q=exports.Media||(exports.Media={})).SM="sm",q.MD="md",q.LG="lg";const Y=t.css`
	&:hover {
		${({$highlightOnHover:e,theme:t})=>e&&t.rows.highlightOnHoverStyle};
	}
`,K=t.css`
	&:hover {
		cursor: pointer;
	}
`,J=i.default.div.attrs((e=>({style:e.style})))`
	display: flex;
	align-items: stretch;
	align-content: stretch;
	width: 100%;
	box-sizing: border-box;
	${({theme:e})=>e.rows.style};
	${({$dense:e,theme:t})=>e&&t.rows.denseStyle};
	${({$striped:e,theme:t})=>e&&t.rows.stripedStyle};
	${({$highlightOnHover:e})=>e&&Y};
	${({$pointerOnHover:e})=>e&&K};
	${({$selected:e,theme:t})=>e&&t.rows.selectedHighlightStyle};
	${({$conditionalStyle:e})=>e};
`;function Q({columns:e=[],conditionalRowStyles:t=[],defaultExpanded:n=!1,defaultExpanderDisabled:o=!1,dense:a=!1,expandableIcon:r,expandableRows:i=!1,expandableRowsComponent:d,expandableRowsComponentProps:c,expandableRowsHideExpander:g,expandOnRowClicked:u=!1,expandOnRowDoubleClicked:p=!1,highlightOnHover:f=!1,id:h,expandableInheritConditionalStyles:x,keyField:C,onRowClicked:y=b,onRowDoubleClicked:v=b,onRowMouseEnter:R=b,onRowMouseLeave:S=b,onRowExpandToggled:E=b,onSelectedRow:O=b,pointerOnHover:$=!1,row:P,rowCount:k,rowIndex:D,selectableRowDisabled:H=null,selectableRows:j=!1,selectableRowsComponent:T,selectableRowsComponentProps:I,selectableRowsHighlight:M=!1,selectableRowsSingle:L=!1,selected:_,striped:z=!1,draggingColumnId:W,onDragStart:V,onDragOver:U,onDragEnd:q,onDragEnter:Y,onDragLeave:K}){const[Q,X]=l.useState(n);l.useEffect((()=>{X(n)}),[n]);const Z=l.useCallback((()=>{X(!Q),E(!Q,P)}),[Q,E,P]),ee=$||i&&(u||p),te=l.useCallback((e=>{e.target.getAttribute("data-tag")===G&&(y(P,e),!o&&i&&u&&Z())}),[o,u,i,Z,y,P]),ne=l.useCallback((e=>{e.target.getAttribute("data-tag")===G&&(v(P,e),!o&&i&&p&&Z())}),[o,p,i,Z,v,P]),oe=l.useCallback((e=>{R(P,e)}),[R,P]),ae=l.useCallback((e=>{S(P,e)}),[S,P]),le=s(P,C),{conditionalStyle:re,classNames:ie}=m(P,t,["rdt_TableRow"]),se=M&&_,de=x?re:{},ce=z&&D%2==0;return l.createElement(l.Fragment,null,l.createElement(J,{id:`row-${h}`,role:"row",$striped:ce,$highlightOnHover:f,$pointerOnHover:!o&&ee,$dense:a,onClick:te,onDoubleClick:ne,onMouseEnter:oe,onMouseLeave:ae,className:ie,$selected:se,$conditionalStyle:re},j&&l.createElement(A,{name:`select-row-${le}`,keyField:C,row:P,rowCount:k,selected:_,selectableRowsComponent:T,selectableRowsComponentProps:I,selectableRowDisabled:H,selectableRowsSingle:L,onSelectedRow:O}),i&&!g&&l.createElement(N,{id:le,expandableIcon:r,expanded:Q,row:P,onToggled:Z,disabled:o}),e.map((e=>e.omit?null:l.createElement(F,{id:`cell-${e.id}-${le}`,key:`cell-${e.id}-${le}`,dataTag:e.ignoreRowClick||e.button?null:G,column:e,row:P,rowIndex:D,isDragging:w(W,e.id),onDragStart:V,onDragOver:U,onDragEnd:q,onDragEnter:Y,onDragLeave:K})))),i&&Q&&l.createElement(B,{key:`expander-${le}`,data:P,extendedRowStyle:de,extendedClassNames:ie,ExpanderComponent:d,expanderComponentProps:c}))}const X=i.default.span`
	padding: 2px;
	color: inherit;
	flex-grow: 0;
	flex-shrink: 0;
	${({$sortActive:e})=>e?"opacity: 1":"opacity: 0"};
	${({$sortDirection:e})=>"desc"===e&&"transform: rotate(180deg)"};
`,Z=({sortActive:e,sortDirection:t})=>r.default.createElement(X,{$sortActive:e,$sortDirection:t},"▲"),ee=i.default(D)`
	${({button:e})=>e&&"text-align: center"};
	${({theme:e,$isDragging:t})=>t&&e.headCells.draggingStyle};
`,te=t.css`
	cursor: pointer;
	span.__rdt_custom_sort_icon__ {
		i,
		svg {
			transform: 'translate3d(0, 0, 0)';
			${({$sortActive:e})=>e?"opacity: 1":"opacity: 0"};
			color: inherit;
			font-size: 18px;
			height: 18px;
			width: 18px;
			backface-visibility: hidden;
			transform-style: preserve-3d;
			transition-duration: 95ms;
			transition-property: transform;
		}

		&.asc i,
		&.asc svg {
			transform: rotate(180deg);
		}
	}

	${({$sortActive:e})=>!e&&t.css`
			&:hover,
			&:focus {
				opacity: 0.7;

				span,
				span.__rdt_custom_sort_icon__ * {
					opacity: 0.7;
				}
			}
		`};
`,ne=i.default.div`
	display: inline-flex;
	align-items: center;
	justify-content: inherit;
	height: 100%;
	width: 100%;
	outline: none;
	user-select: none;
	overflow: hidden;
	${({disabled:e})=>!e&&te};
`,oe=i.default.div`
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
`;var ae=l.memo((function({column:e,disabled:t,draggingColumnId:n,selectedColumn:o={},sortDirection:r,sortIcon:i,sortServer:s,pagination:d,paginationServer:c,persistSelectedOnSort:g,selectableRowsVisibleOnly:u,onSort:p,onDragStart:b,onDragOver:m,onDragEnd:f,onDragEnter:h,onDragLeave:x}){l.useEffect((()=>{"string"==typeof e.selector&&console.error(`Warning: ${e.selector} is a string based column selector which has been deprecated as of v7 and will be removed in v8. Instead, use a selector function e.g. row => row[field]...`)}),[]);const[C,y]=l.useState(!1),v=l.useRef(null);if(l.useEffect((()=>{v.current&&y(v.current.scrollWidth>v.current.clientWidth)}),[C]),e.omit)return null;const R=()=>{if(!e.sortable&&!e.selector)return;let t=r;w(o.id,e.id)&&(t=r===a.ASC?a.DESC:a.ASC),p({type:"SORT_CHANGE",sortDirection:t,selectedColumn:e,clearSelectedOnSort:d&&c&&!g||s||u})},S=e=>l.createElement(Z,{sortActive:e,sortDirection:r}),E=()=>l.createElement("span",{className:[r,"__rdt_custom_sort_icon__"].join(" ")},i),O=!(!e.sortable||!w(o.id,e.id)),$=!e.sortable||t,P=e.sortable&&!i&&!e.right,k=e.sortable&&!i&&e.right,D=e.sortable&&i&&!e.right,H=e.sortable&&i&&e.right;return l.createElement(ee,{"data-column-id":e.id,className:"rdt_TableCol",$headCell:!0,allowOverflow:e.allowOverflow,button:e.button,compact:e.compact,grow:e.grow,hide:e.hide,maxWidth:e.maxWidth,minWidth:e.minWidth,right:e.right,center:e.center,width:e.width,draggable:e.reorder,$isDragging:w(e.id,n),onDragStart:b,onDragOver:m,onDragEnd:f,onDragEnter:h,onDragLeave:x},e.name&&l.createElement(ne,{"data-column-id":e.id,"data-sort-id":e.id,role:"columnheader",tabIndex:0,className:"rdt_TableCol_Sortable",onClick:$?void 0:R,onKeyPress:$?void 0:e=>{"Enter"===e.key&&R()},$sortActive:!$&&O,disabled:$},!$&&H&&E(),!$&&k&&S(O),"string"==typeof e.name?l.createElement(oe,{title:C?e.name:void 0,ref:v,"data-column-id":e.id},e.name):e.name,!$&&D&&E(),!$&&P&&S(O)))}));const le=i.default(k)`
	flex: 0 0 48px;
	justify-content: center;
	align-items: center;
	user-select: none;
	white-space: nowrap;
	font-size: unset;
`;function re({headCell:e=!0,rowData:t,keyField:n,allSelected:o,mergeSelections:a,selectedRows:r,selectableRowsComponent:i,selectableRowsComponentProps:s,selectableRowDisabled:d,onSelectAllRows:c}){const g=r.length>0&&!o,u=d?t.filter((e=>!d(e))):t,p=0===u.length,b=Math.min(t.length,u.length);return l.createElement(le,{className:"rdt_TableCol",$headCell:e,$noPadding:!0},l.createElement(I,{name:"select-all-rows",component:i,componentOptions:s,onClick:()=>{c({type:"SELECT_ALL_ROWS",rows:u,rowCount:b,mergeSelections:a,keyField:n})},checked:o,indeterminate:g,disabled:p}))}function ie(e=exports.Direction.AUTO){const t="object"==typeof window,[n,o]=l.useState(!1);return l.useEffect((()=>{if(t)if("auto"!==e)o("rtl"===e);else{const e=!(!window.document||!window.document.createElement),t=document.getElementsByTagName("BODY")[0],n=document.getElementsByTagName("HTML")[0],a="rtl"===t.dir||"rtl"===n.dir;o(e&&a)}}),[e,t]),n}const se=i.default.div`
	display: flex;
	align-items: center;
	flex: 1 0 auto;
	height: 100%;
	color: ${({theme:e})=>e.contextMenu.fontColor};
	font-size: ${({theme:e})=>e.contextMenu.fontSize};
	font-weight: 400;
`,de=i.default.div`
	display: flex;
	align-items: center;
	justify-content: flex-end;
	flex-wrap: wrap;
`,ce=i.default.div`
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	box-sizing: inherit;
	z-index: 1;
	align-items: center;
	justify-content: space-between;
	display: flex;
	${({$rtl:e})=>e&&"direction: rtl"};
	${({theme:e})=>e.contextMenu.style};
	${({theme:e,$visible:t})=>t&&e.contextMenu.activeStyle};
`;function ge({contextMessage:e,contextActions:t,contextComponent:n,selectedCount:o,direction:a}){const r=ie(a),i=o>0;return n?l.createElement(ce,{$visible:i},l.cloneElement(n,{selectedCount:o})):l.createElement(ce,{$visible:i,$rtl:r},l.createElement(se,null,((e,t,n)=>{if(0===t)return null;const o=1===t?e.singular:e.plural;return n?`${t} ${e.message||""} ${o}`:`${t} ${o} ${e.message||""}`})(e,o,r)),l.createElement(de,null,t))}const ue=i.default.div`
	position: relative;
	box-sizing: border-box;
	overflow: hidden;
	display: flex;
	flex: 1 1 auto;
	align-items: center;
	justify-content: space-between;
	width: 100%;
	flex-wrap: wrap;
	${({theme:e})=>e.header.style}
`,pe=i.default.div`
	flex: 1 0 auto;
	color: ${({theme:e})=>e.header.fontColor};
	font-size: ${({theme:e})=>e.header.fontSize};
	font-weight: 400;
`,be=i.default.div`
	flex: 1 0 auto;
	display: flex;
	align-items: center;
	justify-content: flex-end;

	> * {
		margin-left: 5px;
	}
`,me=({title:e,actions:t=null,contextMessage:n,contextActions:o,contextComponent:a,selectedCount:r,direction:i,showMenu:s=!0})=>l.createElement(ue,{className:"rdt_TableHeader",role:"heading","aria-level":1},l.createElement(pe,null,e),t&&l.createElement(be,null,t),s&&l.createElement(ge,{contextMessage:n,contextActions:o,contextComponent:a,direction:i,selectedCount:r}));function fe(e,t){var n={};for(var o in e)Object.prototype.hasOwnProperty.call(e,o)&&t.indexOf(o)<0&&(n[o]=e[o]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var a=0;for(o=Object.getOwnPropertySymbols(e);a<o.length;a++)t.indexOf(o[a])<0&&Object.prototype.propertyIsEnumerable.call(e,o[a])&&(n[o[a]]=e[o[a]])}return n}"function"==typeof SuppressedError&&SuppressedError;const he={left:"flex-start",right:"flex-end",center:"center"},we=i.default.header`
	position: relative;
	display: flex;
	flex: 1 1 auto;
	box-sizing: border-box;
	align-items: center;
	padding: 4px 16px 4px 24px;
	width: 100%;
	justify-content: ${({align:e})=>he[e]};
	flex-wrap: ${({$wrapContent:e})=>e?"wrap":"nowrap"};
	${({theme:e})=>e.subHeader.style}
`,xe=e=>{var{align:t="right",wrapContent:n=!0}=e,o=fe(e,["align","wrapContent"]);return l.createElement(we,Object.assign({align:t,$wrapContent:n},o))},Ce=i.default.div`
	display: flex;
	flex-direction: column;
`,ye=i.default.div`
	position: relative;
	width: 100%;
	border-radius: inherit;
	${({$responsive:e,$fixedHeader:n})=>e&&t.css`
			overflow-x: auto;

			// hidden prevents vertical scrolling in firefox when fixedHeader is disabled
			overflow-y: ${n?"auto":"hidden"};
			min-height: 0;
		`};

	${({$fixedHeader:e=!1,$fixedHeaderScrollHeight:n="100vh"})=>e&&t.css`
			max-height: ${n};
			-webkit-overflow-scrolling: touch;
		`};

	${({theme:e})=>e.responsiveWrapper.style};
`,ve=i.default.div`
	position: relative;
	box-sizing: border-box;
	width: 100%;
	height: 100%;
	${e=>e.theme.progress.style};
`,Re=i.default.div`
	position: relative;
	width: 100%;
	${({theme:e})=>e.tableWrapper.style};
`,Se=i.default(k)`
	white-space: nowrap;
	${({theme:e})=>e.expanderCell.style};
`,Ee=i.default.div`
	box-sizing: border-box;
	width: 100%;
	height: 100%;
	${({theme:e})=>e.noData.style};
`,Oe=()=>r.default.createElement("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24"},r.default.createElement("path",{d:"M7 10l5 5 5-5z"}),r.default.createElement("path",{d:"M0 0h24v24H0z",fill:"none"})),$e=i.default.select`
	cursor: pointer;
	height: 24px;
	max-width: 100%;
	user-select: none;
	padding-left: 8px;
	padding-right: 24px;
	box-sizing: content-box;
	font-size: inherit;
	color: inherit;
	border: none;
	background-color: transparent;
	appearance: none;
	direction: ltr;
	flex-shrink: 0;

	&::-ms-expand {
		display: none;
	}

	&:disabled::-ms-expand {
		background: #f60;
	}

	option {
		color: initial;
	}
`,Pe=i.default.div`
	position: relative;
	flex-shrink: 0;
	font-size: inherit;
	color: inherit;
	margin-top: 1px;

	svg {
		top: 0;
		right: 0;
		color: inherit;
		position: absolute;
		fill: currentColor;
		width: 24px;
		height: 24px;
		display: inline-block;
		user-select: none;
		pointer-events: none;
	}
`,ke=e=>{var{defaultValue:t,onChange:n}=e,o=fe(e,["defaultValue","onChange"]);return l.createElement(Pe,null,l.createElement($e,Object.assign({onChange:n,defaultValue:t},o)),l.createElement(Oe,null))},De={columns:[],data:[],title:"",keyField:"id",selectableRows:!1,selectableRowsHighlight:!1,selectableRowsNoSelectAll:!1,selectableRowSelected:null,selectableRowDisabled:null,selectableRowsComponent:"input",selectableRowsComponentProps:{},selectableRowsVisibleOnly:!1,selectableRowsSingle:!1,clearSelectedRows:!1,expandableRows:!1,expandableRowDisabled:null,expandableRowExpanded:null,expandOnRowClicked:!1,expandableRowsHideExpander:!1,expandOnRowDoubleClicked:!1,expandableInheritConditionalStyles:!1,expandableRowsComponent:function(){return r.default.createElement("div",null,"To add an expander pass in a component instance via ",r.default.createElement("strong",null,"expandableRowsComponent"),". You can then access props.data from this component.")},expandableIcon:{collapsed:r.default.createElement((()=>r.default.createElement("svg",{fill:"currentColor",height:"24",viewBox:"0 0 24 24",width:"24",xmlns:"http://www.w3.org/2000/svg"},r.default.createElement("path",{d:"M8.59 16.34l4.58-4.59-4.58-4.59L10 5.75l6 6-6 6z"}),r.default.createElement("path",{d:"M0-.25h24v24H0z",fill:"none"}))),null),expanded:r.default.createElement((()=>r.default.createElement("svg",{fill:"currentColor",height:"24",viewBox:"0 0 24 24",width:"24",xmlns:"http://www.w3.org/2000/svg"},r.default.createElement("path",{d:"M7.41 7.84L12 12.42l4.59-4.58L18 9.25l-6 6-6-6z"}),r.default.createElement("path",{d:"M0-.75h24v24H0z",fill:"none"}))),null)},expandableRowsComponentProps:{},progressPending:!1,progressComponent:r.default.createElement("div",{style:{fontSize:"24px",fontWeight:700,padding:"24px"}},"Loading..."),persistTableHead:!1,sortIcon:null,sortFunction:null,sortServer:!1,striped:!1,highlightOnHover:!1,pointerOnHover:!1,noContextMenu:!1,contextMessage:{singular:"item",plural:"items",message:"selected"},actions:null,contextActions:null,contextComponent:null,defaultSortFieldId:null,defaultSortAsc:!0,responsive:!0,noDataComponent:r.default.createElement("div",{style:{padding:"24px"}},"There are no records to display"),disabled:!1,noTableHead:!1,noHeader:!1,subHeader:!1,subHeaderAlign:exports.Alignment.RIGHT,subHeaderWrap:!0,subHeaderComponent:null,fixedHeader:!1,fixedHeaderScrollHeight:"100vh",pagination:!1,paginationServer:!1,paginationServerOptions:{persistSelectedOnSort:!1,persistSelectedOnPageChange:!1},paginationDefaultPage:1,paginationResetDefaultPage:!1,paginationTotalRows:0,paginationPerPage:10,paginationRowsPerPageOptions:[10,15,20,25,30],paginationComponent:null,paginationComponentOptions:{},paginationIconFirstPage:r.default.createElement((()=>r.default.createElement("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24","aria-hidden":"true",role:"presentation"},r.default.createElement("path",{d:"M18.41 16.59L13.82 12l4.59-4.59L17 6l-6 6 6 6zM6 6h2v12H6z"}),r.default.createElement("path",{fill:"none",d:"M24 24H0V0h24v24z"}))),null),paginationIconLastPage:r.default.createElement((()=>r.default.createElement("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24","aria-hidden":"true",role:"presentation"},r.default.createElement("path",{d:"M5.59 7.41L10.18 12l-4.59 4.59L7 18l6-6-6-6zM16 6h2v12h-2z"}),r.default.createElement("path",{fill:"none",d:"M0 0h24v24H0V0z"}))),null),paginationIconNext:r.default.createElement((()=>r.default.createElement("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24","aria-hidden":"true",role:"presentation"},r.default.createElement("path",{d:"M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"}),r.default.createElement("path",{d:"M0 0h24v24H0z",fill:"none"}))),null),paginationIconPrevious:r.default.createElement((()=>r.default.createElement("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24","aria-hidden":"true",role:"presentation"},r.default.createElement("path",{d:"M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"}),r.default.createElement("path",{d:"M0 0h24v24H0z",fill:"none"}))),null),dense:!1,conditionalRowStyles:[],theme:"default",customStyles:{},direction:exports.Direction.AUTO,onChangePage:b,onChangeRowsPerPage:b,onRowClicked:b,onRowDoubleClicked:b,onRowMouseEnter:b,onRowMouseLeave:b,onRowExpandToggled:b,onSelectedRowsChange:b,onSort:b,onColumnOrderChange:b},He={rowsPerPageText:"Rows per page:",rangeSeparatorText:"of",noRowsPerPage:!1,selectAllRowsItem:!1,selectAllRowsItemText:"All"},je=i.default.nav`
	display: flex;
	flex: 1 1 auto;
	justify-content: flex-end;
	align-items: center;
	box-sizing: border-box;
	padding-right: 8px;
	padding-left: 8px;
	width: 100%;
	${({theme:e})=>e.pagination.style};
`,Fe=i.default.button`
	position: relative;
	display: block;
	user-select: none;
	border: none;
	${({theme:e})=>e.pagination.pageButtonsStyle};
	${({$isRTL:e})=>e&&"transform: scale(-1, -1)"};
`,Te=i.default.div`
	display: flex;
	align-items: center;
	border-radius: 4px;
	white-space: nowrap;
	${E`
    width: 100%;
    justify-content: space-around;
  `};
`,Ie=i.default.span`
	flex-shrink: 1;
	user-select: none;
`,Me=i.default(Ie)`
	margin: 0 24px;
`,Ae=i.default(Ie)`
	margin: 0 4px;
`;var Le=l.memo((function({rowsPerPage:e,rowCount:t,currentPage:n,direction:o=De.direction,paginationRowsPerPageOptions:a=De.paginationRowsPerPageOptions,paginationIconLastPage:r=De.paginationIconLastPage,paginationIconFirstPage:i=De.paginationIconFirstPage,paginationIconNext:s=De.paginationIconNext,paginationIconPrevious:d=De.paginationIconPrevious,paginationComponentOptions:c=De.paginationComponentOptions,onChangeRowsPerPage:g=De.onChangeRowsPerPage,onChangePage:p=De.onChangePage}){const b=(()=>{const e="object"==typeof window;function t(){return{width:e?window.innerWidth:void 0,height:e?window.innerHeight:void 0}}const[n,o]=l.useState(t);return l.useEffect((()=>{if(!e)return()=>null;function n(){o(t())}return window.addEventListener("resize",n),()=>window.removeEventListener("resize",n)}),[]),n})(),m=ie(o),f=b.width&&b.width>599,h=u(t,e),w=n*e,x=w-e+1,C=1===n,y=n===h,v=Object.assign(Object.assign({},He),c),R=n===h?`${x}-${t} ${v.rangeSeparatorText} ${t}`:`${x}-${w} ${v.rangeSeparatorText} ${t}`,S=l.useCallback((()=>p(n-1)),[n,p]),E=l.useCallback((()=>p(n+1)),[n,p]),O=l.useCallback((()=>p(1)),[p]),$=l.useCallback((()=>p(u(t,e))),[p,t,e]),P=l.useCallback((e=>g(Number(e.target.value),n)),[n,g]),k=a.map((e=>l.createElement("option",{key:e,value:e},e)));v.selectAllRowsItem&&k.push(l.createElement("option",{key:-1,value:t},v.selectAllRowsItemText));const D=l.createElement(ke,{onChange:P,defaultValue:e,"aria-label":v.rowsPerPageText},k);return l.createElement(je,{className:"rdt_Pagination"},!v.noRowsPerPage&&f&&l.createElement(l.Fragment,null,l.createElement(Ae,null,v.rowsPerPageText),D),f&&l.createElement(Me,null,R),l.createElement(Te,null,l.createElement(Fe,{id:"pagination-first-page",type:"button","aria-label":"First Page","aria-disabled":C,onClick:O,disabled:C,$isRTL:m},i),l.createElement(Fe,{id:"pagination-previous-page",type:"button","aria-label":"Previous Page","aria-disabled":C,onClick:S,disabled:C,$isRTL:m},d),!v.noRowsPerPage&&!f&&D,l.createElement(Fe,{id:"pagination-next-page",type:"button","aria-label":"Next Page","aria-disabled":y,onClick:E,disabled:y,$isRTL:m},s),l.createElement(Fe,{id:"pagination-last-page",type:"button","aria-label":"Last Page","aria-disabled":y,onClick:$,disabled:y,$isRTL:m},r)))}));const _e=(e,t)=>{const n=l.useRef(!0);l.useEffect((()=>{n.current?n.current=!1:e()}),t)};function ze(e){return e&&e.__esModule&&Object.prototype.hasOwnProperty.call(e,"default")?e.default:e}var Ne=function(e){return function(e){return!!e&&"object"==typeof e}(e)&&!function(e){var t=Object.prototype.toString.call(e);return"[object RegExp]"===t||"[object Date]"===t||function(e){return e.$$typeof===We}(e)}(e)};var We="function"==typeof Symbol&&Symbol.for?Symbol.for("react.element"):60103;function Be(e,t){return!1!==t.clone&&t.isMergeableObject(e)?Ye((n=e,Array.isArray(n)?[]:{}),e,t):e;// removed by dead control flow
 var n; }function Ge(e,t,n){return e.concat(t).map((function(e){return Be(e,n)}))}function Ve(e){return Object.keys(e).concat(function(e){return Object.getOwnPropertySymbols?Object.getOwnPropertySymbols(e).filter((function(t){return Object.propertyIsEnumerable.call(e,t)})):[]}(e))}function Ue(e,t){try{return t in e}catch(e){return!1}}function qe(e,t,n){var o={};return n.isMergeableObject(e)&&Ve(e).forEach((function(t){o[t]=Be(e[t],n)})),Ve(t).forEach((function(a){(function(e,t){return Ue(e,t)&&!(Object.hasOwnProperty.call(e,t)&&Object.propertyIsEnumerable.call(e,t))})(e,a)||(Ue(e,a)&&n.isMergeableObject(t[a])?o[a]=function(e,t){if(!t.customMerge)return Ye;var n=t.customMerge(e);return"function"==typeof n?n:Ye}(a,n)(e[a],t[a],n):o[a]=Be(t[a],n))})),o}function Ye(e,t,n){(n=n||{}).arrayMerge=n.arrayMerge||Ge,n.isMergeableObject=n.isMergeableObject||Ne,n.cloneUnlessOtherwiseSpecified=Be;var o=Array.isArray(t);return o===Array.isArray(e)?o?n.arrayMerge(e,t,n):qe(e,t,n):Be(t,n)}Ye.all=function(e,t){if(!Array.isArray(e))throw new Error("first argument should be an array");return e.reduce((function(e,n){return Ye(e,n,t)}),{})};var Ke=ze(Ye);const Je={text:{primary:"rgba(0, 0, 0, 0.87)",secondary:"rgba(0, 0, 0, 0.54)",disabled:"rgba(0, 0, 0, 0.38)"},background:{default:"#FFFFFF"},context:{background:"#e3f2fd",text:"rgba(0, 0, 0, 0.87)"},divider:{default:"rgba(0,0,0,.12)"},button:{default:"rgba(0,0,0,.54)",focus:"rgba(0,0,0,.12)",hover:"rgba(0,0,0,.12)",disabled:"rgba(0, 0, 0, .18)"},selected:{default:"#e3f2fd",text:"rgba(0, 0, 0, 0.87)"},highlightOnHover:{default:"#EEEEEE",text:"rgba(0, 0, 0, 0.87)"},striped:{default:"#FAFAFA",text:"rgba(0, 0, 0, 0.87)"}},Qe={default:Je,light:Je,dark:{text:{primary:"#FFFFFF",secondary:"rgba(255, 255, 255, 0.7)",disabled:"rgba(0,0,0,.12)"},background:{default:"#424242"},context:{background:"#E91E63",text:"#FFFFFF"},divider:{default:"rgba(81, 81, 81, 1)"},button:{default:"#FFFFFF",focus:"rgba(255, 255, 255, .54)",hover:"rgba(255, 255, 255, .12)",disabled:"rgba(255, 255, 255, .18)"},selected:{default:"rgba(0, 0, 0, .7)",text:"#FFFFFF"},highlightOnHover:{default:"rgba(0, 0, 0, .7)",text:"#FFFFFF"},striped:{default:"rgba(0, 0, 0, .87)",text:"#FFFFFF"}}};function Xe(e,t,n,o){const[r,i]=l.useState((()=>g(e))),[s,d]=l.useState(""),c=l.useRef("");_e((()=>{i(g(e))}),[e]);const u=l.useCallback((e=>{var t,n,o;const{attributes:a}=e.target,l=null===(t=a.getNamedItem("data-column-id"))||void 0===t?void 0:t.value;l&&(c.current=(null===(o=null===(n=r[h(r,l)])||void 0===n?void 0:n.id)||void 0===o?void 0:o.toString())||"",d(c.current))}),[r]),p=l.useCallback((e=>{var n;const{attributes:o}=e.target,a=null===(n=o.getNamedItem("data-column-id"))||void 0===n?void 0:n.value;if(a&&c.current&&a!==c.current){const e=h(r,c.current),n=h(r,a),o=[...r];o[e]=r[n],o[n]=r[e],i(o),t(o)}}),[t,r]),b=l.useCallback((e=>{e.preventDefault()}),[]),m=l.useCallback((e=>{e.preventDefault()}),[]),f=l.useCallback((e=>{e.preventDefault(),c.current="",d("")}),[]),w=function(e=!1){return e?a.ASC:a.DESC}(o),x=l.useMemo((()=>r[h(r,null==n?void 0:n.toString())]||{}),[n,r]);return{tableColumns:r,draggingColumnId:s,handleDragStart:u,handleDragEnter:p,handleDragOver:b,handleDragLeave:m,handleDragEnd:f,defaultSortDirection:w,defaultSortColumn:x}}var Ze=l.memo((function(e){const{data:n=De.data,columns:o=De.columns,title:r=De.title,actions:i=De.actions,keyField:d=De.keyField,striped:c=De.striped,highlightOnHover:g=De.highlightOnHover,pointerOnHover:b=De.pointerOnHover,dense:m=De.dense,selectableRows:h=De.selectableRows,selectableRowsSingle:w=De.selectableRowsSingle,selectableRowsHighlight:C=De.selectableRowsHighlight,selectableRowsNoSelectAll:v=De.selectableRowsNoSelectAll,selectableRowsVisibleOnly:E=De.selectableRowsVisibleOnly,selectableRowSelected:O=De.selectableRowSelected,selectableRowDisabled:$=De.selectableRowDisabled,selectableRowsComponent:P=De.selectableRowsComponent,selectableRowsComponentProps:D=De.selectableRowsComponentProps,onRowExpandToggled:H=De.onRowExpandToggled,onSelectedRowsChange:j=De.onSelectedRowsChange,expandableIcon:F=De.expandableIcon,onChangeRowsPerPage:T=De.onChangeRowsPerPage,onChangePage:I=De.onChangePage,paginationServer:M=De.paginationServer,paginationServerOptions:A=De.paginationServerOptions,paginationTotalRows:L=De.paginationTotalRows,paginationDefaultPage:_=De.paginationDefaultPage,paginationResetDefaultPage:z=De.paginationResetDefaultPage,paginationPerPage:N=De.paginationPerPage,paginationRowsPerPageOptions:W=De.paginationRowsPerPageOptions,paginationIconLastPage:B=De.paginationIconLastPage,paginationIconFirstPage:G=De.paginationIconFirstPage,paginationIconNext:V=De.paginationIconNext,paginationIconPrevious:U=De.paginationIconPrevious,paginationComponent:q=De.paginationComponent,paginationComponentOptions:Y=De.paginationComponentOptions,responsive:K=De.responsive,progressPending:J=De.progressPending,progressComponent:X=De.progressComponent,persistTableHead:Z=De.persistTableHead,noDataComponent:ee=De.noDataComponent,disabled:te=De.disabled,noTableHead:ne=De.noTableHead,noHeader:oe=De.noHeader,fixedHeader:le=De.fixedHeader,fixedHeaderScrollHeight:ie=De.fixedHeaderScrollHeight,pagination:se=De.pagination,subHeader:de=De.subHeader,subHeaderAlign:ce=De.subHeaderAlign,subHeaderWrap:ge=De.subHeaderWrap,subHeaderComponent:ue=De.subHeaderComponent,noContextMenu:pe=De.noContextMenu,contextMessage:be=De.contextMessage,contextActions:fe=De.contextActions,contextComponent:he=De.contextComponent,expandableRows:we=De.expandableRows,onRowClicked:Oe=De.onRowClicked,onRowDoubleClicked:$e=De.onRowDoubleClicked,onRowMouseEnter:Pe=De.onRowMouseEnter,onRowMouseLeave:ke=De.onRowMouseLeave,sortIcon:He=De.sortIcon,onSort:je=De.onSort,sortFunction:Fe=De.sortFunction,sortServer:Te=De.sortServer,expandableRowsComponent:Ie=De.expandableRowsComponent,expandableRowsComponentProps:Me=De.expandableRowsComponentProps,expandableRowDisabled:Ae=De.expandableRowDisabled,expandableRowsHideExpander:ze=De.expandableRowsHideExpander,expandOnRowClicked:Ne=De.expandOnRowClicked,expandOnRowDoubleClicked:We=De.expandOnRowDoubleClicked,expandableRowExpanded:Be=De.expandableRowExpanded,expandableInheritConditionalStyles:Ge=De.expandableInheritConditionalStyles,defaultSortFieldId:Ve=De.defaultSortFieldId,defaultSortAsc:Ue=De.defaultSortAsc,clearSelectedRows:qe=De.clearSelectedRows,conditionalRowStyles:Ye=De.conditionalRowStyles,theme:Je=De.theme,customStyles:Ze=De.customStyles,direction:et=De.direction,onColumnOrderChange:tt=De.onColumnOrderChange,className:nt,ariaLabel:ot}=e,{tableColumns:at,draggingColumnId:lt,handleDragStart:rt,handleDragEnter:it,handleDragOver:st,handleDragLeave:dt,handleDragEnd:ct,defaultSortDirection:gt,defaultSortColumn:ut}=Xe(o,tt,Ve,Ue),[{rowsPerPage:pt,currentPage:bt,selectedRows:mt,allSelected:ft,selectedCount:ht,selectedColumn:wt,sortDirection:xt,toggleOnSelectedRowsChange:Ct},yt]=l.useReducer(x,{allSelected:!1,selectedCount:0,selectedRows:[],selectedColumn:ut,toggleOnSelectedRowsChange:!1,sortDirection:gt,currentPage:_,rowsPerPage:N,selectedRowsFlag:!1,contextMessage:De.contextMessage}),{persistSelectedOnSort:vt=!1,persistSelectedOnPageChange:Rt=!1}=A,St=!(!M||!Rt&&!vt),Et=se&&!J&&n.length>0,Ot=q||Le,$t=l.useMemo((()=>((e={},t="default",n="default")=>{const o=Qe[t]?t:n;return Ke({table:{style:{color:(a=Qe[o]).text.primary,backgroundColor:a.background.default}},tableWrapper:{style:{display:"table"}},responsiveWrapper:{style:{}},header:{style:{fontSize:"22px",color:a.text.primary,backgroundColor:a.background.default,minHeight:"56px",paddingLeft:"16px",paddingRight:"8px"}},subHeader:{style:{backgroundColor:a.background.default,minHeight:"52px"}},head:{style:{color:a.text.primary,fontSize:"12px",fontWeight:500}},headRow:{style:{backgroundColor:a.background.default,minHeight:"52px",borderBottomWidth:"1px",borderBottomColor:a.divider.default,borderBottomStyle:"solid"},denseStyle:{minHeight:"32px"}},headCells:{style:{paddingLeft:"16px",paddingRight:"16px"},draggingStyle:{cursor:"move"}},contextMenu:{style:{backgroundColor:a.context.background,fontSize:"18px",fontWeight:400,color:a.context.text,paddingLeft:"16px",paddingRight:"8px",transform:"translate3d(0, -100%, 0)",transitionDuration:"125ms",transitionTimingFunction:"cubic-bezier(0, 0, 0.2, 1)",willChange:"transform"},activeStyle:{transform:"translate3d(0, 0, 0)"}},cells:{style:{paddingLeft:"16px",paddingRight:"16px",wordBreak:"break-word"},draggingStyle:{}},rows:{style:{fontSize:"13px",fontWeight:400,color:a.text.primary,backgroundColor:a.background.default,minHeight:"48px","&:not(:last-of-type)":{borderBottomStyle:"solid",borderBottomWidth:"1px",borderBottomColor:a.divider.default}},denseStyle:{minHeight:"32px"},selectedHighlightStyle:{"&:nth-of-type(n)":{color:a.selected.text,backgroundColor:a.selected.default,borderBottomColor:a.background.default}},highlightOnHoverStyle:{color:a.highlightOnHover.text,backgroundColor:a.highlightOnHover.default,transitionDuration:"0.15s",transitionProperty:"background-color",borderBottomColor:a.background.default,outlineStyle:"solid",outlineWidth:"1px",outlineColor:a.background.default},stripedStyle:{color:a.striped.text,backgroundColor:a.striped.default}},expanderRow:{style:{color:a.text.primary,backgroundColor:a.background.default}},expanderCell:{style:{flex:"0 0 48px"}},expanderButton:{style:{color:a.button.default,fill:a.button.default,backgroundColor:"transparent",borderRadius:"2px",transition:"0.25s",height:"100%",width:"100%","&:hover:enabled":{cursor:"pointer"},"&:disabled":{color:a.button.disabled},"&:hover:not(:disabled)":{cursor:"pointer",backgroundColor:a.button.hover},"&:focus":{outline:"none",backgroundColor:a.button.focus},svg:{margin:"auto"}}},pagination:{style:{color:a.text.secondary,fontSize:"13px",minHeight:"56px",backgroundColor:a.background.default,borderTopStyle:"solid",borderTopWidth:"1px",borderTopColor:a.divider.default},pageButtonsStyle:{borderRadius:"50%",height:"40px",width:"40px",padding:"8px",margin:"px",cursor:"pointer",transition:"0.4s",color:a.button.default,fill:a.button.default,backgroundColor:"transparent","&:disabled":{cursor:"unset",color:a.button.disabled,fill:a.button.disabled},"&:hover:not(:disabled)":{backgroundColor:a.button.hover},"&:focus":{outline:"none",backgroundColor:a.button.focus}}},noData:{style:{display:"flex",alignItems:"center",justifyContent:"center",color:a.text.primary,backgroundColor:a.background.default}},progress:{style:{display:"flex",alignItems:"center",justifyContent:"center",color:a.text.primary,backgroundColor:a.background.default}}},e);// removed by dead control flow
 var a; })(Ze,Je)),[Ze,Je]),Pt=l.useMemo((()=>Object.assign({},"auto"!==et&&{dir:et})),[et]),kt=l.useMemo((()=>{if(Te)return n;if((null==wt?void 0:wt.sortFunction)&&"function"==typeof wt.sortFunction){const e=wt.sortFunction,t=xt===a.ASC?e:(t,n)=>-1*e(t,n);return[...n].sort(t)}return function(e,t,n,o){return t?o&&"function"==typeof o?o(e.slice(0),t,n):e.slice(0).sort(((e,o)=>{const a=t(e),l=t(o);if("asc"===n){if(a<l)return-1;if(a>l)return 1}if("desc"===n){if(a>l)return-1;if(a<l)return 1}return 0})):e}(n,null==wt?void 0:wt.selector,xt,Fe)}),[Te,wt,xt,n,Fe]),Dt=l.useMemo((()=>{if(se&&!M){const e=bt*pt,t=e-pt;return kt.slice(t,e)}return kt}),[bt,se,M,pt,kt]),Ht=l.useCallback((e=>{yt(e)}),[]),jt=l.useCallback((e=>{yt(e)}),[]),Ft=l.useCallback((e=>{yt(e)}),[]),Tt=l.useCallback(((e,t)=>Oe(e,t)),[Oe]),It=l.useCallback(((e,t)=>$e(e,t)),[$e]),Mt=l.useCallback(((e,t)=>Pe(e,t)),[Pe]),At=l.useCallback(((e,t)=>ke(e,t)),[ke]),Lt=l.useCallback((e=>yt({type:"CHANGE_PAGE",page:e,paginationServer:M,visibleOnly:E,persistSelectedOnPageChange:Rt})),[M,Rt,E]),_t=l.useCallback((e=>{const t=u(L||Dt.length,e),n=p(bt,t);M||Lt(n),yt({type:"CHANGE_ROWS_PER_PAGE",page:n,rowsPerPage:e})}),[bt,Lt,M,L,Dt.length]);if(se&&!M&&kt.length>0&&0===Dt.length){const e=u(kt.length,pt),t=p(bt,e);Lt(t)}_e((()=>{j({allSelected:ft,selectedCount:ht,selectedRows:mt.slice(0)})}),[Ct]),_e((()=>{je(wt,xt,kt.slice(0))}),[wt,xt]),_e((()=>{I(bt,L||kt.length)}),[bt]),_e((()=>{T(pt,bt)}),[pt]),_e((()=>{Lt(_)}),[_,z]),_e((()=>{if(se&&M&&L>0){const e=u(L,pt),t=p(bt,e);bt!==t&&Lt(t)}}),[L]),l.useEffect((()=>{yt({type:"CLEAR_SELECTED_ROWS",selectedRowsFlag:qe})}),[w,qe]),l.useEffect((()=>{if(!O)return;const e=kt.filter((e=>O(e))),t=w?e.slice(0,1):e;yt({type:"SELECT_MULTIPLE_ROWS",keyField:d,selectedRows:t,totalRows:kt.length,mergeSelections:St})}),[n,O]);const zt=E?Dt:kt,Nt=Rt||w||v;return l.createElement(t.ThemeProvider,{theme:$t},!oe&&(!!r||!!i)&&l.createElement(me,{title:r,actions:i,showMenu:!pe,selectedCount:ht,direction:et,contextActions:fe,contextComponent:he,contextMessage:be}),de&&l.createElement(xe,{align:ce,wrapContent:ge},ue),l.createElement(ye,Object.assign({$responsive:K,$fixedHeader:le,$fixedHeaderScrollHeight:ie,className:nt},Pt),l.createElement(Re,null,J&&!Z&&l.createElement(ve,null,X),l.createElement(y,Object.assign({disabled:te,className:"rdt_Table",role:"table"},ot&&{"aria-label":ot}),!ne&&(!!Z||kt.length>0&&!J)&&l.createElement(R,{className:"rdt_TableHead",role:"rowgroup",$fixedHeader:le},l.createElement(S,{className:"rdt_TableHeadRow",role:"row",$dense:m},h&&(Nt?l.createElement(k,{style:{flex:"0 0 48px"}}):l.createElement(re,{allSelected:ft,selectedRows:mt,selectableRowsComponent:P,selectableRowsComponentProps:D,selectableRowDisabled:$,rowData:zt,keyField:d,mergeSelections:St,onSelectAllRows:jt})),we&&!ze&&l.createElement(Se,null),at.map((e=>l.createElement(ae,{key:e.id,column:e,selectedColumn:wt,disabled:J||0===kt.length,pagination:se,paginationServer:M,persistSelectedOnSort:vt,selectableRowsVisibleOnly:E,sortDirection:xt,sortIcon:He,sortServer:Te,onSort:Ht,onDragStart:rt,onDragOver:st,onDragEnd:ct,onDragEnter:it,onDragLeave:dt,draggingColumnId:lt}))))),!kt.length&&!J&&l.createElement(Ee,null,ee),J&&Z&&l.createElement(ve,null,X),!J&&kt.length>0&&l.createElement(Ce,{className:"rdt_TableBody",role:"rowgroup"},Dt.map(((e,t)=>{const n=s(e,d),o=function(e=""){return"number"!=typeof e&&(!e||0===e.length)}(n)?t:n,a=f(e,mt,d),r=!!(we&&Be&&Be(e)),i=!!(we&&Ae&&Ae(e));return l.createElement(Q,{id:o,key:o,keyField:d,"data-row-id":o,columns:at,row:e,rowCount:kt.length,rowIndex:t,selectableRows:h,expandableRows:we,expandableIcon:F,highlightOnHover:g,pointerOnHover:b,dense:m,expandOnRowClicked:Ne,expandOnRowDoubleClicked:We,expandableRowsComponent:Ie,expandableRowsComponentProps:Me,expandableRowsHideExpander:ze,defaultExpanderDisabled:i,defaultExpanded:r,expandableInheritConditionalStyles:Ge,conditionalRowStyles:Ye,selected:a,selectableRowsHighlight:C,selectableRowsComponent:P,selectableRowsComponentProps:D,selectableRowDisabled:$,selectableRowsSingle:w,striped:c,onRowExpandToggled:H,onRowClicked:Tt,onRowDoubleClicked:It,onRowMouseEnter:Mt,onRowMouseLeave:At,onSelectedRow:Ft,draggingColumnId:lt,onDragStart:rt,onDragOver:st,onDragEnd:ct,onDragEnter:it,onDragLeave:dt})})))))),Et&&l.createElement("div",null,l.createElement(Ot,{onChangePage:Lt,onChangeRowsPerPage:_t,rowCount:L||kt.length,currentPage:bt,rowsPerPage:pt,direction:et,paginationRowsPerPageOptions:W,paginationIconLastPage:B,paginationIconFirstPage:G,paginationIconNext:V,paginationIconPrevious:U,paginationComponentOptions:Y})))}));exports.STOP_PROP_TAG=G,exports.createTheme=function(e="default",t,n="default"){return Qe[e]||(Qe[e]=Ke(Qe[n],t||{})),Qe[e]=Ke(Qe[e],t||{}),Qe[e]},exports["default"]=Ze,exports.defaultThemes=Qe;
//# sourceMappingURL=index.cjs.js.map


/***/ }),

/***/ "./node_modules/react-player/dist/HtmlPlayer.js":
/*!******************************************************!*\
  !*** ./node_modules/react-player/dist/HtmlPlayer.js ***!
  \******************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ HtmlPlayer_default)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var _patterns_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./patterns.js */ "./node_modules/react-player/dist/patterns.js");


const HtmlPlayer = react__WEBPACK_IMPORTED_MODULE_0__.forwardRef((props, ref) => {
  const Media = _patterns_js__WEBPACK_IMPORTED_MODULE_1__.AUDIO_EXTENSIONS.test(`${props.src}`) ? "audio" : "video";
  return /* @__PURE__ */ react__WEBPACK_IMPORTED_MODULE_0__.createElement(Media, { ...props, ref }, props.children);
});
var HtmlPlayer_default = HtmlPlayer;



/***/ }),

/***/ "./node_modules/react-player/dist/Player.js":
/*!**************************************************!*\
  !*** ./node_modules/react-player/dist/Player.js ***!
  \**************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Player_default)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");

const Player = react__WEBPACK_IMPORTED_MODULE_0__.forwardRef((props, ref) => {
  const { playing, pip } = props;
  const Player2 = props.activePlayer;
  const playerRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const startOnPlayRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(true);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    var _a, _b;
    if (!playerRef.current) return;
    if (playerRef.current.paused && playing === true) {
      playerRef.current.play();
    }
    if (!playerRef.current.paused && playing === false) {
      playerRef.current.pause();
    }
    playerRef.current.playbackRate = (_a = props.playbackRate) != null ? _a : 1;
    playerRef.current.volume = (_b = props.volume) != null ? _b : 1;
  });
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    var _a, _b, _c, _d, _e;
    if (!playerRef.current || !globalThis.document) return;
    if (pip && !document.pictureInPictureElement) {
      try {
        (_b = (_a = playerRef.current).requestPictureInPicture) == null ? void 0 : _b.call(_a);
      } catch (err) {
      }
    }
    if (!pip && document.pictureInPictureElement) {
      try {
        (_d = (_c = playerRef.current).exitPictureInPicture) == null ? void 0 : _d.call(_c);
        (_e = document.exitPictureInPicture) == null ? void 0 : _e.call(document);
      } catch (err) {
      }
    }
  }, [pip]);
  const handleLoadStart = (event) => {
    var _a, _b;
    startOnPlayRef.current = true;
    (_a = props.onReady) == null ? void 0 : _a.call(props);
    (_b = props.onLoadStart) == null ? void 0 : _b.call(props, event);
  };
  const handlePlay = (event) => {
    var _a, _b;
    if (startOnPlayRef.current) {
      startOnPlayRef.current = false;
      (_a = props.onStart) == null ? void 0 : _a.call(props, event);
    }
    (_b = props.onPlay) == null ? void 0 : _b.call(props, event);
  };
  if (!Player2) {
    return null;
  }
  const eventProps = {};
  const reactPlayerEventHandlers = ["onReady", "onStart"];
  for (const key in props) {
    if (key.startsWith("on") && !reactPlayerEventHandlers.includes(key)) {
      eventProps[key] = props[key];
    }
  }
  return /* @__PURE__ */ react__WEBPACK_IMPORTED_MODULE_0__.createElement(
    Player2,
    {
      ...eventProps,
      style: props.style,
      className: props.className,
      slot: props.slot,
      ref: (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)(
        (node) => {
          playerRef.current = node;
          if (typeof ref === "function") {
            ref(node);
          } else if (ref !== null) {
            ref.current = node;
          }
        },
        [ref]
      ),
      src: props.src,
      crossOrigin: props.crossOrigin,
      preload: props.preload,
      controls: props.controls,
      muted: props.muted,
      autoPlay: props.autoPlay,
      loop: props.loop,
      playsInline: props.playsInline,
      config: props.config,
      onLoadStart: handleLoadStart,
      onPlay: handlePlay
    },
    props.children
  );
});
Player.displayName = "Player";
var Player_default = Player;



/***/ }),

/***/ "./node_modules/react-player/dist/ReactPlayer.js":
/*!*******************************************************!*\
  !*** ./node_modules/react-player/dist/ReactPlayer.js ***!
  \*******************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createReactPlayer: () => (/* binding */ createReactPlayer)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var _props_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./props.js */ "./node_modules/react-player/dist/props.js");
/* harmony import */ var _Player_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Player.js */ "./node_modules/react-player/dist/Player.js");



const Preview = (0,react__WEBPACK_IMPORTED_MODULE_0__.lazy)(() => __webpack_require__.e(/*! import() | reactPlayerPreview */ "reactPlayerPreview").then(__webpack_require__.bind(__webpack_require__, /*! ./Preview.js */ "./node_modules/react-player/dist/Preview.js")));
const customPlayers = [];
const createReactPlayer = (players, playerFallback) => {
  const getActivePlayer = (src) => {
    for (const player of [...customPlayers, ...players]) {
      if (src && player.canPlay(src)) {
        return player;
      }
    }
    if (playerFallback) {
      return playerFallback;
    }
    return null;
  };
  const ReactPlayer = react__WEBPACK_IMPORTED_MODULE_0__.forwardRef((_props, ref) => {
    const props = { ..._props_js__WEBPACK_IMPORTED_MODULE_1__.defaultProps, ..._props };
    const { src, slot, className, style, width, height, fallback, wrapper } = props;
    const [showPreview, setShowPreview] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(!!props.light);
    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
      if (props.light) {
        setShowPreview(true);
      } else {
        setShowPreview(false);
      }
    }, [props.light]);
    const handleClickPreview = (e) => {
      var _a;
      setShowPreview(false);
      (_a = props.onClickPreview) == null ? void 0 : _a.call(props, e);
    };
    const renderPreview = (src2) => {
      if (!src2) return null;
      const { light, playIcon, previewTabIndex, oEmbedUrl, previewAriaLabel } = props;
      return /* @__PURE__ */ react__WEBPACK_IMPORTED_MODULE_0__.createElement(
        Preview,
        {
          src: src2,
          light,
          playIcon,
          previewTabIndex,
          previewAriaLabel,
          oEmbedUrl,
          onClickPreview: handleClickPreview
        }
      );
    };
    const renderActivePlayer = (src2) => {
      var _a, _b;
      const player = getActivePlayer(src2);
      if (!player) return null;
      const { style: style2, width: width2, height: height2, wrapper: wrapper2 } = props;
      const config = (_a = props.config) == null ? void 0 : _a[player.key];
      return /* @__PURE__ */ react__WEBPACK_IMPORTED_MODULE_0__.createElement(
        _Player_js__WEBPACK_IMPORTED_MODULE_2__["default"],
        {
          ...props,
          ref,
          activePlayer: (_b = player.player) != null ? _b : player,
          slot: wrapper2 ? void 0 : slot,
          className: wrapper2 ? void 0 : className,
          style: wrapper2 ? { display: "block", width: "100%", height: "100%" } : { display: "block", width: width2, height: height2, ...style2 },
          config
        }
      );
    };
    const Wrapper = wrapper == null ? ForwardChildren : wrapper;
    const UniversalSuspense = fallback === false ? ForwardChildren : react__WEBPACK_IMPORTED_MODULE_0__.Suspense;
    return /* @__PURE__ */ react__WEBPACK_IMPORTED_MODULE_0__.createElement(Wrapper, { slot, className, style: { width, height, ...style } }, /* @__PURE__ */ react__WEBPACK_IMPORTED_MODULE_0__.createElement(UniversalSuspense, { fallback }, showPreview ? renderPreview(src) : renderActivePlayer(src)));
  });
  ReactPlayer.displayName = "ReactPlayer";
  ReactPlayer.addCustomPlayer = (player) => {
    customPlayers.push(player);
  };
  ReactPlayer.removeCustomPlayers = () => {
    customPlayers.length = 0;
  };
  ReactPlayer.canPlay = (src) => {
    if (src) {
      for (const Player2 of [...customPlayers, ...players]) {
        if (Player2.canPlay(src)) {
          return true;
        }
      }
    }
    return false;
  };
  ReactPlayer.canEnablePIP = (src) => {
    var _a;
    if (src) {
      for (const Player2 of [...customPlayers, ...players]) {
        if (Player2.canPlay(src) && ((_a = Player2.canEnablePIP) == null ? void 0 : _a.call(Player2))) {
          return true;
        }
      }
    }
    return false;
  };
  return ReactPlayer;
};
const ForwardChildren = ({ children }) => children;



/***/ }),

/***/ "./node_modules/react-player/dist/index.js":
/*!*************************************************!*\
  !*** ./node_modules/react-player/dist/index.js ***!
  \*************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ src_default)
/* harmony export */ });
/* harmony import */ var _players_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./players.js */ "./node_modules/react-player/dist/players.js");
/* harmony import */ var _ReactPlayer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ReactPlayer.js */ "./node_modules/react-player/dist/ReactPlayer.js");
"use client";


const fallback = _players_js__WEBPACK_IMPORTED_MODULE_0__["default"][_players_js__WEBPACK_IMPORTED_MODULE_0__["default"].length - 1];
var src_default = (0,_ReactPlayer_js__WEBPACK_IMPORTED_MODULE_1__.createReactPlayer)(_players_js__WEBPACK_IMPORTED_MODULE_0__["default"], fallback);



/***/ }),

/***/ "./node_modules/react-player/dist/patterns.js":
/*!****************************************************!*\
  !*** ./node_modules/react-player/dist/patterns.js ***!
  \****************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AUDIO_EXTENSIONS: () => (/* binding */ AUDIO_EXTENSIONS),
/* harmony export */   DASH_EXTENSIONS: () => (/* binding */ DASH_EXTENSIONS),
/* harmony export */   HLS_EXTENSIONS: () => (/* binding */ HLS_EXTENSIONS),
/* harmony export */   MATCH_URL_MUX: () => (/* binding */ MATCH_URL_MUX),
/* harmony export */   MATCH_URL_SPOTIFY: () => (/* binding */ MATCH_URL_SPOTIFY),
/* harmony export */   MATCH_URL_TIKTOK: () => (/* binding */ MATCH_URL_TIKTOK),
/* harmony export */   MATCH_URL_TWITCH: () => (/* binding */ MATCH_URL_TWITCH),
/* harmony export */   MATCH_URL_VIMEO: () => (/* binding */ MATCH_URL_VIMEO),
/* harmony export */   MATCH_URL_WISTIA: () => (/* binding */ MATCH_URL_WISTIA),
/* harmony export */   MATCH_URL_YOUTUBE: () => (/* binding */ MATCH_URL_YOUTUBE),
/* harmony export */   VIDEO_EXTENSIONS: () => (/* binding */ VIDEO_EXTENSIONS),
/* harmony export */   canPlay: () => (/* binding */ canPlay)
/* harmony export */ });
const AUDIO_EXTENSIONS = /\.(m4a|m4b|mp4a|mpga|mp2|mp2a|mp3|m2a|m3a|wav|weba|aac|oga|spx)($|\?)/i;
const VIDEO_EXTENSIONS = /\.(mp4|og[gv]|webm|mov|m4v)(#t=[,\d+]+)?($|\?)/i;
const HLS_EXTENSIONS = /\.(m3u8)($|\?)/i;
const DASH_EXTENSIONS = /\.(mpd)($|\?)/i;
const MATCH_URL_MUX = /stream\.mux\.com\/(?!\w+\.m3u8)(\w+)/;
const MATCH_URL_YOUTUBE = /(?:youtu\.be\/|youtube(?:-nocookie|education)?\.com\/(?:embed\/|v\/|watch\/|watch\?v=|watch\?.+&v=|shorts\/|live\/))((\w|-){11})|youtube\.com\/playlist\?list=|youtube\.com\/user\//;
const MATCH_URL_VIMEO = /vimeo\.com\/(?!progressive_redirect).+/;
const MATCH_URL_WISTIA = /(?:wistia\.(?:com|net)|wi\.st)\/(?:medias|embed)\/(?:iframe\/)?([^?]+)/;
const MATCH_URL_SPOTIFY = /open\.spotify\.com\/(\w+)\/(\w+)/i;
const MATCH_URL_TWITCH = /(?:www\.|go\.)?twitch\.tv\/([a-zA-Z0-9_]+|(videos?\/|\?video=)\d+)($|\?)/;
const MATCH_URL_TIKTOK = /tiktok\.com\/(?:player\/v1\/|share\/video\/|@[^/]+\/video\/)([0-9]+)/;
const canPlayFile = (url, test) => {
  if (Array.isArray(url)) {
    for (const item of url) {
      if (typeof item === "string" && canPlayFile(item, test)) {
        return true;
      }
      if (canPlayFile(item.src, test)) {
        return true;
      }
    }
    return false;
  }
  return test(url);
};
const canPlay = {
  html: (url) => canPlayFile(url, (u) => AUDIO_EXTENSIONS.test(u) || VIDEO_EXTENSIONS.test(u)),
  hls: (url) => canPlayFile(url, (u) => HLS_EXTENSIONS.test(u)),
  dash: (url) => canPlayFile(url, (u) => DASH_EXTENSIONS.test(u)),
  mux: (url) => MATCH_URL_MUX.test(url),
  youtube: (url) => MATCH_URL_YOUTUBE.test(url),
  vimeo: (url) => MATCH_URL_VIMEO.test(url) && !VIDEO_EXTENSIONS.test(url) && !HLS_EXTENSIONS.test(url),
  wistia: (url) => MATCH_URL_WISTIA.test(url),
  spotify: (url) => MATCH_URL_SPOTIFY.test(url),
  twitch: (url) => MATCH_URL_TWITCH.test(url),
  tiktok: (url) => MATCH_URL_TIKTOK.test(url)
};



/***/ }),

/***/ "./node_modules/react-player/dist/players.js":
/*!***************************************************!*\
  !*** ./node_modules/react-player/dist/players.js ***!
  \***************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ players_default)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var _patterns_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./patterns.js */ "./node_modules/react-player/dist/patterns.js");
/* harmony import */ var _HtmlPlayer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./HtmlPlayer.js */ "./node_modules/react-player/dist/HtmlPlayer.js");



const Players = [
  {
    key: "hls",
    name: "hls.js",
    canPlay: _patterns_js__WEBPACK_IMPORTED_MODULE_1__.canPlay.hls,
    canEnablePIP: () => true,
    player: (0,react__WEBPACK_IMPORTED_MODULE_0__.lazy)(
      () => Promise.all(/*! import() | reactPlayerHls */[__webpack_require__.e("vendors-node_modules_custom-media-element_dist_custom-media-element_js"), __webpack_require__.e("vendors-node_modules_hls_js_dist_hls_mjs-node_modules_media-tracks_dist_index_js"), __webpack_require__.e("reactPlayerHls")]).then(__webpack_require__.bind(__webpack_require__, /*! hls-video-element/react */ "./node_modules/hls-video-element/dist/react.js"))
    )
  },
  {
    key: "dash",
    name: "dash.js",
    canPlay: _patterns_js__WEBPACK_IMPORTED_MODULE_1__.canPlay.dash,
    canEnablePIP: () => true,
    player: (0,react__WEBPACK_IMPORTED_MODULE_0__.lazy)(
      () => Promise.all(/*! import() | reactPlayerDash */[__webpack_require__.e("vendors-node_modules_custom-media-element_dist_custom-media-element_js"), __webpack_require__.e("reactPlayerDash")]).then(__webpack_require__.bind(__webpack_require__, /*! dash-video-element/react */ "./node_modules/dash-video-element/dist/react.js"))
    )
  },
  {
    key: "mux",
    name: "Mux",
    canPlay: _patterns_js__WEBPACK_IMPORTED_MODULE_1__.canPlay.mux,
    canEnablePIP: () => true,
    player: (0,react__WEBPACK_IMPORTED_MODULE_0__.lazy)(
      () => Promise.all(/*! import() | reactPlayerMux */[__webpack_require__.e("vendors-node_modules_custom-media-element_dist_custom-media-element_js"), __webpack_require__.e("vendors-node_modules_hls_js_dist_hls_mjs-node_modules_media-tracks_dist_index_js"), __webpack_require__.e("reactPlayerMux")]).then(__webpack_require__.bind(__webpack_require__, /*! @mux/mux-player-react */ "./node_modules/@mux/mux-player-react/dist/index.mjs"))
    )
  },
  {
    key: "youtube",
    name: "YouTube",
    canPlay: _patterns_js__WEBPACK_IMPORTED_MODULE_1__.canPlay.youtube,
    player: (0,react__WEBPACK_IMPORTED_MODULE_0__.lazy)(
      () => __webpack_require__.e(/*! import() | reactPlayerYouTube */ "reactPlayerYouTube").then(__webpack_require__.bind(__webpack_require__, /*! youtube-video-element/react */ "./node_modules/youtube-video-element/dist/react.js"))
    )
  },
  {
    key: "vimeo",
    name: "Vimeo",
    canPlay: _patterns_js__WEBPACK_IMPORTED_MODULE_1__.canPlay.vimeo,
    player: (0,react__WEBPACK_IMPORTED_MODULE_0__.lazy)(
      () => __webpack_require__.e(/*! import() | reactPlayerVimeo */ "reactPlayerVimeo").then(__webpack_require__.bind(__webpack_require__, /*! vimeo-video-element/react */ "./node_modules/vimeo-video-element/dist/react.js"))
    )
  },
  {
    key: "wistia",
    name: "Wistia",
    canPlay: _patterns_js__WEBPACK_IMPORTED_MODULE_1__.canPlay.wistia,
    canEnablePIP: () => true,
    player: (0,react__WEBPACK_IMPORTED_MODULE_0__.lazy)(
      () => __webpack_require__.e(/*! import() | reactPlayerWistia */ "reactPlayerWistia").then(__webpack_require__.bind(__webpack_require__, /*! wistia-video-element/react */ "./node_modules/wistia-video-element/dist/react.js"))
    )
  },
  {
    key: "spotify",
    name: "Spotify",
    canPlay: _patterns_js__WEBPACK_IMPORTED_MODULE_1__.canPlay.spotify,
    canEnablePIP: () => false,
    player: (0,react__WEBPACK_IMPORTED_MODULE_0__.lazy)(
      () => __webpack_require__.e(/*! import() | reactPlayerSpotify */ "reactPlayerSpotify").then(__webpack_require__.bind(__webpack_require__, /*! spotify-audio-element/react */ "./node_modules/spotify-audio-element/dist/react.js"))
    )
  },
  {
    key: "twitch",
    name: "Twitch",
    canPlay: _patterns_js__WEBPACK_IMPORTED_MODULE_1__.canPlay.twitch,
    canEnablePIP: () => false,
    player: (0,react__WEBPACK_IMPORTED_MODULE_0__.lazy)(
      () => __webpack_require__.e(/*! import() | reactPlayerTwitch */ "reactPlayerTwitch").then(__webpack_require__.bind(__webpack_require__, /*! twitch-video-element/react */ "./node_modules/twitch-video-element/dist/react.js"))
    )
  },
  {
    key: "tiktok",
    name: "TikTok",
    canPlay: _patterns_js__WEBPACK_IMPORTED_MODULE_1__.canPlay.tiktok,
    canEnablePIP: () => false,
    player: (0,react__WEBPACK_IMPORTED_MODULE_0__.lazy)(
      () => __webpack_require__.e(/*! import() | reactPlayerTiktok */ "reactPlayerTiktok").then(__webpack_require__.bind(__webpack_require__, /*! tiktok-video-element/react */ "./node_modules/tiktok-video-element/dist/react.js"))
    )
  },
  {
    key: "html",
    name: "html",
    canPlay: _patterns_js__WEBPACK_IMPORTED_MODULE_1__.canPlay.html,
    canEnablePIP: () => true,
    player: _HtmlPlayer_js__WEBPACK_IMPORTED_MODULE_2__["default"]
  }
];
var players_default = Players;



/***/ }),

/***/ "./node_modules/react-player/dist/props.js":
/*!*************************************************!*\
  !*** ./node_modules/react-player/dist/props.js ***!
  \*************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   defaultProps: () => (/* binding */ defaultProps)
/* harmony export */ });
const defaultProps = {
  // Falsy values don't need to be defined
  //
  // native video attrs
  // src: undefined,
  // preload: undefined,
  // crossOrigin: undefined,
  // autoPlay: false,
  // muted: false,
  // loop: false,
  // controls: false,
  // playsInline: false,
  width: "320px",
  height: "180px",
  // native video props
  volume: 1,
  playbackRate: 1,
  // custom props
  // playing: undefined,
  // pip: false,
  // light: false,
  // fallback: null,
  previewTabIndex: 0,
  previewAriaLabel: "",
  oEmbedUrl: "https://noembed.com/embed?url={url}"
};



/***/ }),

/***/ "./node_modules/react-terminal-ui/build/index.es.js":
/*!**********************************************************!*\
  !*** ./node_modules/react-terminal-ui/build/index.es.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ColorMode: () => (/* binding */ o),
/* harmony export */   TerminalInput: () => (/* binding */ i),
/* harmony export */   TerminalOutput: () => (/* binding */ l),
/* harmony export */   "default": () => (/* binding */ d)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
function a(n,e){var t="function"==typeof Symbol&&n[Symbol.iterator];if(!t)return n;var r,a,i=t.call(n),l=[];try{for(;(void 0===e||e-- >0)&&!(r=i.next()).done;)l.push(r.value)}catch(n){a={error:n}}finally{try{r&&!r.done&&(t=i.return)&&t.call(i)}finally{if(a)throw a.error}}return l}"function"==typeof SuppressedError&&SuppressedError;var i=function(e){var t=e.children,r=e.prompt;return react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div",{className:"react-terminal-line react-terminal-input","data-terminal-prompt":r||"$"},t)},l=function(e){var t=e.children;return react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div",{className:"react-terminal-line"},t)};!function(n,e){void 0===e&&(e={});var t=e.insertAt;if(n&&"undefined"!=typeof document){var r=document.head||document.getElementsByTagName("head")[0],a=document.createElement("style");a.type="text/css","top"===t&&r.firstChild?r.insertBefore(a,r.firstChild):r.appendChild(a),a.styleSheet?a.styleSheet.cssText=n:a.appendChild(document.createTextNode(n))}}("/**\n * Modfied version of [termynal.js](https://github.com/ines/termynal/blob/master/termynal.css).\n *\n * @author Ines Montani <ines@ines.io>\n * @version 0.0.1\n * @license MIT\n */\n .react-terminal-wrapper {\n  width: 100%;\n  background: #252a33;\n  color: #eee;\n  font-size: 18px;\n  font-family: 'Fira Mono', Consolas, Menlo, Monaco, 'Courier New', Courier, monospace;\n  border-radius: 4px;\n  padding: 75px 45px 35px;\n  position: relative;\n  -webkit-box-sizing: border-box;\n          box-sizing: border-box;\n }\n\n.react-terminal {\n  overflow: auto;\n  display: flex;\n  flex-direction: column;\n}\n\n.react-terminal-wrapper.react-terminal-light {\n  background: #ddd;\n  color: #1a1e24;\n}\n\n.react-terminal-window-buttons {\n  position: absolute;\n  top: 15px;\n  left: 15px;\n  display: flex;\n  flex-direction: row;\n  gap: 10px;\n}\n\n.react-terminal-window-buttons button {\n  width: 15px;\n  height: 15px;\n  border-radius: 50%;\n  border: 0;\n}\n\n.react-terminal-window-buttons button.clickable {\n  cursor: pointer;\n}\n\n.react-terminal-window-buttons button.red-btn {\n  background: #d9515d;\n}\n\n.react-terminal-window-buttons button.yellow-btn {\n  background: #f4c025;\n}\n\n.react-terminal-window-buttons button.green-btn {\n  background: #3ec930;\n}\n\n.react-terminal-wrapper:after {\n  content: attr(data-terminal-name);\n  position: absolute;\n  color: #a2a2a2;\n  top: 5px;\n  left: 0;\n  width: 100%;\n  text-align: center;\n  pointer-events: none;\n}\n\n.react-terminal-wrapper.react-terminal-light:after {\n  color: #D76D77;\n}\n\n.react-terminal-line {\n  white-space: pre;\n}\n\n.react-terminal-line:before {\n  /* Set up defaults and ensure empty lines are displayed. */\n  content: '';\n  display: inline-block;\n  vertical-align: middle;\n  color: #a2a2a2;\n}\n\n.react-terminal-light .react-terminal-line:before {\n  color: #D76D77;\n}\n\n.react-terminal-input:before {\n  margin-right: 0.75em;\n  content: '$';\n}\n\n.react-terminal-input[data-terminal-prompt]:before {\n  content: attr(data-terminal-prompt);\n}\n\n.react-terminal-wrapper:focus-within .react-terminal-active-input .cursor {\n  position: relative;\n  display: inline-block;\n  width: 0.55em;\n  height: 1em;\n  top: 0.225em;\n  background: #fff;\n  -webkit-animation: blink 1s infinite;\n          animation: blink 1s infinite;\n}\n\n/* Cursor animation */\n\n@-webkit-keyframes blink {\n  50% {\n      opacity: 0;\n  }\n}\n\n@keyframes blink {\n  50% {\n      opacity: 0;\n  }\n}\n\n.terminal-hidden-input {\n    position: fixed;\n    left: -1000px;\n}\n\n/* .react-terminal-progress {\n  display: flex;\n  margin: .5rem 0;\n}\n\n.react-terminal-progress-bar {\n  background-color: #fff;\n  border-radius: .25rem;\n  width: 25%;\n}\n\n.react-terminal-wrapper.react-terminal-light .react-terminal-progress-bar {\n  background-color: #000;\n} */\n");var o,c=function(e){var t=e.redBtnCallback,r=e.yellowBtnCallback,a=e.greenBtnCallback;return react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div",{className:"react-terminal-window-buttons"},react__WEBPACK_IMPORTED_MODULE_0___default().createElement("button",{className:"".concat(r?"clickable":""," red-btn"),disabled:!t,onClick:t}),react__WEBPACK_IMPORTED_MODULE_0___default().createElement("button",{className:"".concat(r?"clickable":""," yellow-btn"),disabled:!r,onClick:r}),react__WEBPACK_IMPORTED_MODULE_0___default().createElement("button",{className:"".concat(a?"clickable":""," green-btn"),disabled:!a,onClick:a}))};!function(n){n[n.Light=0]="Light",n[n.Dark=1]="Dark"}(o||(o={}));var d=function(i){var l=i.name,d=i.prompt,s=i.height,m=void 0===s?"600px":s,u=i.colorMode,p=i.onInput,f=i.children,b=i.startingInputValue,y=void 0===b?"":b,h=i.redBtnCallback,v=i.yellowBtnCallback,w=i.greenBtnCallback,g=i.TopButtonsPanel,k=void 0===g?c:g,x=a((0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(""),2),C=x[0],E=x[1],S=a((0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(0),2),N=S[0],B=S[1],D=(0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)((function(){E(y.trim())}),[y]),(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)((function(){var n,e;if(null!=p){var t=[],r=function(n){var e=function(){var e;return null===(e=null==n?void 0:n.querySelector(".terminal-hidden-input"))||void 0===e?void 0:e.focus()};null==n||n.addEventListener("click",e),t.push({terminalEl:n,listener:e})};try{for(var a=function(n){var e="function"==typeof Symbol&&Symbol.iterator,t=e&&n[e],r=0;if(t)return t.call(n);if(n&&"number"==typeof n.length)return{next:function(){return n&&r>=n.length&&(n=void 0),{value:n&&n[r++],done:!n}}};throw new TypeError(e?"Object is not iterable.":"Symbol.iterator is not defined.")}(document.getElementsByClassName("react-terminal-wrapper")),i=a.next();!i.done;i=a.next()){r(i.value)}}catch(e){n={error:e}}finally{try{i&&!i.done&&(e=a.return)&&e.call(a)}finally{if(n)throw n.error}}return function(){t.forEach((function(n){n.terminalEl.removeEventListener("click",n.listener)}))}}}),[p]);var T=["react-terminal-wrapper"];return u===o.Light&&T.push("react-terminal-light"),react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div",{className:T.join(" "),"data-terminal-name":l},react__WEBPACK_IMPORTED_MODULE_0___default().createElement(k,{redBtnCallback:h,yellowBtnCallback:v,greenBtnCallback:w}),react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div",{className:"react-terminal",style:{height:m}},f,"function"==typeof p&&react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div",{className:"react-terminal-line react-terminal-input react-terminal-active-input","data-terminal-prompt":d||"$",key:"terminal-line-prompt"},C,react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span",{className:"cursor",style:{left:"".concat(N+1,"px")}})),react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div",{ref:D})),react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input",{className:"terminal-hidden-input",placeholder:"Terminal Hidden Input",value:C,autoFocus:null!=p,onChange:function(n){E(n.target.value)},onKeyDown:function(n){var e,t,r;if(p)if("Enter"===n.key)p(C),B(0),E(""),setTimeout((function(){var n;return null===(n=null==D?void 0:D.current)||void 0===n?void 0:n.scrollIntoView({behavior:"auto",block:"nearest"})}),500);else if(["ArrowLeft","ArrowRight","ArrowDown","ArrowUp","Delete"].includes(n.key)){var a=n.currentTarget,i="",l=C.length-(a.selectionStart||0);e=l,t=0,r=C.length,l=e>r?r:e<t?t:e,"ArrowLeft"===n.key?(l>C.length-1&&l--,i=C.slice(C.length-1-l)):"ArrowRight"===n.key||"Delete"===n.key?i=C.slice(C.length-l+1):"ArrowUp"===n.key&&(i=C.slice(0));var o=function(n,e){var t=document.createElement("span");t.style.visibility="hidden",t.style.position="absolute",t.style.fontSize=window.getComputedStyle(n).fontSize,t.style.fontFamily=window.getComputedStyle(n).fontFamily,t.innerText=e,document.body.appendChild(t);var r=t.getBoundingClientRect().width;return document.body.removeChild(t),-r}(a,i);B(o)}}}))};
//# sourceMappingURL=index.es.js.map


/***/ }),

/***/ "./node_modules/shallowequal/index.js":
/*!********************************************!*\
  !*** ./node_modules/shallowequal/index.js ***!
  \********************************************/
/***/ ((module) => {

//

module.exports = function shallowEqual(objA, objB, compare, compareContext) {
  var ret = compare ? compare.call(compareContext, objA, objB) : void 0;

  if (ret !== void 0) {
    return !!ret;
  }

  if (objA === objB) {
    return true;
  }

  if (typeof objA !== "object" || !objA || typeof objB !== "object" || !objB) {
    return false;
  }

  var keysA = Object.keys(objA);
  var keysB = Object.keys(objB);

  if (keysA.length !== keysB.length) {
    return false;
  }

  var bHasOwnProperty = Object.prototype.hasOwnProperty.bind(objB);

  // Test for A's keys different from B.
  for (var idx = 0; idx < keysA.length; idx++) {
    var key = keysA[idx];

    if (!bHasOwnProperty(key)) {
      return false;
    }

    var valueA = objA[key];
    var valueB = objB[key];

    ret = compare ? compare.call(compareContext, valueA, valueB, key) : void 0;

    if (ret === false || (ret === void 0 && valueA !== valueB)) {
      return false;
    }
  }

  return true;
};


/***/ }),

/***/ "./node_modules/styled-components/dist/styled-components.browser.esm.js":
/*!******************************************************************************!*\
  !*** ./node_modules/styled-components/dist/styled-components.browser.esm.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ServerStyleSheet: () => (/* binding */ vt),
/* harmony export */   StyleSheetConsumer: () => (/* binding */ Be),
/* harmony export */   StyleSheetContext: () => (/* binding */ $e),
/* harmony export */   StyleSheetManager: () => (/* binding */ Ye),
/* harmony export */   ThemeConsumer: () => (/* binding */ tt),
/* harmony export */   ThemeContext: () => (/* binding */ et),
/* harmony export */   ThemeProvider: () => (/* binding */ ot),
/* harmony export */   __PRIVATE__: () => (/* binding */ gt),
/* harmony export */   createGlobalStyle: () => (/* binding */ ft),
/* harmony export */   css: () => (/* binding */ lt),
/* harmony export */   "default": () => (/* binding */ dt),
/* harmony export */   isStyledComponent: () => (/* binding */ se),
/* harmony export */   keyframes: () => (/* binding */ mt),
/* harmony export */   styled: () => (/* binding */ dt),
/* harmony export */   useTheme: () => (/* binding */ nt),
/* harmony export */   version: () => (/* binding */ v),
/* harmony export */   withTheme: () => (/* binding */ yt)
/* harmony export */ });
/* harmony import */ var tslib__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tslib */ "./node_modules/styled-components/node_modules/tslib/tslib.es6.mjs");
/* harmony import */ var _emotion_is_prop_valid__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @emotion/is-prop-valid */ "./node_modules/@emotion/is-prop-valid/dist/emotion-is-prop-valid.esm.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var shallowequal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! shallowequal */ "./node_modules/shallowequal/index.js");
/* harmony import */ var shallowequal__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(shallowequal__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var stylis__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! stylis */ "./node_modules/styled-components/node_modules/stylis/src/Enum.js");
/* harmony import */ var stylis__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! stylis */ "./node_modules/styled-components/node_modules/stylis/src/Parser.js");
/* harmony import */ var stylis__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! stylis */ "./node_modules/styled-components/node_modules/stylis/src/Serializer.js");
/* harmony import */ var stylis__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! stylis */ "./node_modules/styled-components/node_modules/stylis/src/Middleware.js");
/* harmony import */ var _emotion_unitless__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @emotion/unitless */ "./node_modules/@emotion/unitless/dist/emotion-unitless.esm.js");
var f="undefined"!=typeof process&&void 0!==process.env&&(process.env.REACT_APP_SC_ATTR||process.env.SC_ATTR)||"data-styled",m="active",y="data-styled-version",v="6.1.12",g="/*!sc*/\n",S="undefined"!=typeof window&&"HTMLElement"in window,w=Boolean("boolean"==typeof SC_DISABLE_SPEEDY?SC_DISABLE_SPEEDY:"undefined"!=typeof process&&void 0!==process.env&&void 0!==process.env.REACT_APP_SC_DISABLE_SPEEDY&&""!==process.env.REACT_APP_SC_DISABLE_SPEEDY?"false"!==process.env.REACT_APP_SC_DISABLE_SPEEDY&&process.env.REACT_APP_SC_DISABLE_SPEEDY:"undefined"!=typeof process&&void 0!==process.env&&void 0!==process.env.SC_DISABLE_SPEEDY&&""!==process.env.SC_DISABLE_SPEEDY?"false"!==process.env.SC_DISABLE_SPEEDY&&process.env.SC_DISABLE_SPEEDY:"production"!=="development"),b={},E=/invalid hook call/i,N=new Set,P=function(t,n){if(true){var o=n?' with the id of "'.concat(n,'"'):"",s="The component ".concat(t).concat(o," has been created dynamically.\n")+"You may see this warning because you've called styled inside another component.\nTo resolve this only create new StyledComponents outside of any render method and function component.",i=console.error;try{var a=!0;console.error=function(t){for(var n=[],o=1;o<arguments.length;o++)n[o-1]=arguments[o];E.test(t)?(a=!1,N.delete(s)):i.apply(void 0,(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__spreadArray)([t],n,!1))},(0,react__WEBPACK_IMPORTED_MODULE_2__.useRef)(),a&&!N.has(s)&&(console.warn(s),N.add(s))}catch(e){E.test(e.message)&&N.delete(s)}finally{console.error=i}}},_=Object.freeze([]),C=Object.freeze({});function I(e,t,n){return void 0===n&&(n=C),e.theme!==n.theme&&e.theme||t||n.theme}var A=new Set(["a","abbr","address","area","article","aside","audio","b","base","bdi","bdo","big","blockquote","body","br","button","canvas","caption","cite","code","col","colgroup","data","datalist","dd","del","details","dfn","dialog","div","dl","dt","em","embed","fieldset","figcaption","figure","footer","form","h1","h2","h3","h4","h5","h6","header","hgroup","hr","html","i","iframe","img","input","ins","kbd","keygen","label","legend","li","link","main","map","mark","menu","menuitem","meta","meter","nav","noscript","object","ol","optgroup","option","output","p","param","picture","pre","progress","q","rp","rt","ruby","s","samp","script","section","select","small","source","span","strong","style","sub","summary","sup","table","tbody","td","textarea","tfoot","th","thead","time","tr","track","u","ul","use","var","video","wbr","circle","clipPath","defs","ellipse","foreignObject","g","image","line","linearGradient","marker","mask","path","pattern","polygon","polyline","radialGradient","rect","stop","svg","text","tspan"]),O=/[!"#$%&'()*+,./:;<=>?@[\\\]^`{|}~-]+/g,D=/(^-|-$)/g;function R(e){return e.replace(O,"-").replace(D,"")}var T=/(a)(d)/gi,k=52,j=function(e){return String.fromCharCode(e+(e>25?39:97))};function x(e){var t,n="";for(t=Math.abs(e);t>k;t=t/k|0)n=j(t%k)+n;return(j(t%k)+n).replace(T,"$1-$2")}var V,F=5381,M=function(e,t){for(var n=t.length;n;)e=33*e^t.charCodeAt(--n);return e},z=function(e){return M(F,e)};function $(e){return x(z(e)>>>0)}function B(e){return true&&"string"==typeof e&&e||e.displayName||e.name||"Component"}function L(e){return"string"==typeof e&&( false||e.charAt(0)===e.charAt(0).toLowerCase())}var G="function"==typeof Symbol&&Symbol.for,Y=G?Symbol.for("react.memo"):60115,W=G?Symbol.for("react.forward_ref"):60112,q={childContextTypes:!0,contextType:!0,contextTypes:!0,defaultProps:!0,displayName:!0,getDefaultProps:!0,getDerivedStateFromError:!0,getDerivedStateFromProps:!0,mixins:!0,propTypes:!0,type:!0},H={name:!0,length:!0,prototype:!0,caller:!0,callee:!0,arguments:!0,arity:!0},U={$$typeof:!0,compare:!0,defaultProps:!0,displayName:!0,propTypes:!0,type:!0},J=((V={})[W]={$$typeof:!0,render:!0,defaultProps:!0,displayName:!0,propTypes:!0},V[Y]=U,V);function X(e){return("type"in(t=e)&&t.type.$$typeof)===Y?U:"$$typeof"in e?J[e.$$typeof]:q;// removed by dead control flow
 var t; }var Z=Object.defineProperty,K=Object.getOwnPropertyNames,Q=Object.getOwnPropertySymbols,ee=Object.getOwnPropertyDescriptor,te=Object.getPrototypeOf,ne=Object.prototype;function oe(e,t,n){if("string"!=typeof t){if(ne){var o=te(t);o&&o!==ne&&oe(e,o,n)}var r=K(t);Q&&(r=r.concat(Q(t)));for(var s=X(e),i=X(t),a=0;a<r.length;++a){var c=r[a];if(!(c in H||n&&n[c]||i&&c in i||s&&c in s)){var l=ee(t,c);try{Z(e,c,l)}catch(e){}}}}return e}function re(e){return"function"==typeof e}function se(e){return"object"==typeof e&&"styledComponentId"in e}function ie(e,t){return e&&t?"".concat(e," ").concat(t):e||t||""}function ae(e,t){if(0===e.length)return"";for(var n=e[0],o=1;o<e.length;o++)n+=t?t+e[o]:e[o];return n}function ce(e){return null!==e&&"object"==typeof e&&e.constructor.name===Object.name&&!("props"in e&&e.$$typeof)}function le(e,t,n){if(void 0===n&&(n=!1),!n&&!ce(e)&&!Array.isArray(e))return t;if(Array.isArray(t))for(var o=0;o<t.length;o++)e[o]=le(e[o],t[o]);else if(ce(t))for(var o in t)e[o]=le(e[o],t[o]);return e}function ue(e,t){Object.defineProperty(e,"toString",{value:t})}var pe= true?{1:"Cannot create styled-component for component: %s.\n\n",2:"Can't collect styles once you've consumed a `ServerStyleSheet`'s styles! `ServerStyleSheet` is a one off instance for each server-side render cycle.\n\n- Are you trying to reuse it across renders?\n- Are you accidentally calling collectStyles twice?\n\n",3:"Streaming SSR is only supported in a Node.js environment; Please do not try to call this method in the browser.\n\n",4:"The `StyleSheetManager` expects a valid target or sheet prop!\n\n- Does this error occur on the client and is your target falsy?\n- Does this error occur on the server and is the sheet falsy?\n\n",5:"The clone method cannot be used on the client!\n\n- Are you running in a client-like environment on the server?\n- Are you trying to run SSR on the client?\n\n",6:"Trying to insert a new style tag, but the given Node is unmounted!\n\n- Are you using a custom target that isn't mounted?\n- Does your document not have a valid head element?\n- Have you accidentally removed a style tag manually?\n\n",7:'ThemeProvider: Please return an object from your "theme" prop function, e.g.\n\n```js\ntheme={() => ({})}\n```\n\n',8:'ThemeProvider: Please make your "theme" prop an object.\n\n',9:"Missing document `<head>`\n\n",10:"Cannot find a StyleSheet instance. Usually this happens if there are multiple copies of styled-components loaded at once. Check out this issue for how to troubleshoot and fix the common cases where this situation can happen: https://github.com/styled-components/styled-components/issues/1941#issuecomment-417862021\n\n",11:"_This error was replaced with a dev-time warning, it will be deleted for v4 final._ [createGlobalStyle] received children which will not be rendered. Please use the component without passing children elements.\n\n",12:"It seems you are interpolating a keyframe declaration (%s) into an untagged string. This was supported in styled-components v3, but is not longer supported in v4 as keyframes are now injected on-demand. Please wrap your string in the css\\`\\` helper which ensures the styles are injected correctly. See https://www.styled-components.com/docs/api#css\n\n",13:"%s is not a styled component and cannot be referred to via component selector. See https://www.styled-components.com/docs/advanced#referring-to-other-components for more details.\n\n",14:'ThemeProvider: "theme" prop is required.\n\n',15:"A stylis plugin has been supplied that is not named. We need a name for each plugin to be able to prevent styling collisions between different stylis configurations within the same app. Before you pass your plugin to `<StyleSheetManager stylisPlugins={[]}>`, please make sure each plugin is uniquely-named, e.g.\n\n```js\nObject.defineProperty(importedPlugin, 'name', { value: 'some-unique-name' });\n```\n\n",16:"Reached the limit of how many styled components may be created at group %s.\nYou may only create up to 1,073,741,824 components. If you're creating components dynamically,\nas for instance in your render method then you may be running into this limitation.\n\n",17:"CSSStyleSheet could not be found on HTMLStyleElement.\nHas styled-components' style tag been unmounted or altered by another script?\n",18:"ThemeProvider: Please make sure your useTheme hook is within a `<ThemeProvider>`"}:0;function de(){for(var e=[],t=0;t<arguments.length;t++)e[t]=arguments[t];for(var n=e[0],o=[],r=1,s=e.length;r<s;r+=1)o.push(e[r]);return o.forEach(function(e){n=n.replace(/%[a-z]/,e)}),n}function he(t){for(var n=[],o=1;o<arguments.length;o++)n[o-1]=arguments[o];return false?0:new Error(de.apply(void 0,(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__spreadArray)([pe[t]],n,!1)).trim())}var fe=function(){function e(e){this.groupSizes=new Uint32Array(512),this.length=512,this.tag=e}return e.prototype.indexOfGroup=function(e){for(var t=0,n=0;n<e;n++)t+=this.groupSizes[n];return t},e.prototype.insertRules=function(e,t){if(e>=this.groupSizes.length){for(var n=this.groupSizes,o=n.length,r=o;e>=r;)if((r<<=1)<0)throw he(16,"".concat(e));this.groupSizes=new Uint32Array(r),this.groupSizes.set(n),this.length=r;for(var s=o;s<r;s++)this.groupSizes[s]=0}for(var i=this.indexOfGroup(e+1),a=(s=0,t.length);s<a;s++)this.tag.insertRule(i,t[s])&&(this.groupSizes[e]++,i++)},e.prototype.clearGroup=function(e){if(e<this.length){var t=this.groupSizes[e],n=this.indexOfGroup(e),o=n+t;this.groupSizes[e]=0;for(var r=n;r<o;r++)this.tag.deleteRule(n)}},e.prototype.getGroup=function(e){var t="";if(e>=this.length||0===this.groupSizes[e])return t;for(var n=this.groupSizes[e],o=this.indexOfGroup(e),r=o+n,s=o;s<r;s++)t+="".concat(this.tag.getRule(s)).concat(g);return t},e}(),me=1<<30,ye=new Map,ve=new Map,ge=1,Se=function(e){if(ye.has(e))return ye.get(e);for(;ve.has(ge);)ge++;var t=ge++;if( true&&((0|t)<0||t>me))throw he(16,"".concat(t));return ye.set(e,t),ve.set(t,e),t},we=function(e,t){ge=t+1,ye.set(e,t),ve.set(t,e)},be="style[".concat(f,"][").concat(y,'="').concat(v,'"]'),Ee=new RegExp("^".concat(f,'\\.g(\\d+)\\[id="([\\w\\d-]+)"\\].*?"([^"]*)')),Ne=function(e,t,n){for(var o,r=n.split(","),s=0,i=r.length;s<i;s++)(o=r[s])&&e.registerName(t,o)},Pe=function(e,t){for(var n,o=(null!==(n=t.textContent)&&void 0!==n?n:"").split(g),r=[],s=0,i=o.length;s<i;s++){var a=o[s].trim();if(a){var c=a.match(Ee);if(c){var l=0|parseInt(c[1],10),u=c[2];0!==l&&(we(u,l),Ne(e,u,c[3]),e.getTag().insertRules(l,r)),r.length=0}else r.push(a)}}},_e=function(e){for(var t=document.querySelectorAll(be),n=0,o=t.length;n<o;n++){var r=t[n];r&&r.getAttribute(f)!==m&&(Pe(e,r),r.parentNode&&r.parentNode.removeChild(r))}};function Ce(){return true?__webpack_require__.nc:0}var Ie=function(e){var t=document.head,n=e||t,o=document.createElement("style"),r=function(e){var t=Array.from(e.querySelectorAll("style[".concat(f,"]")));return t[t.length-1]}(n),s=void 0!==r?r.nextSibling:null;o.setAttribute(f,m),o.setAttribute(y,v);var i=Ce();return i&&o.setAttribute("nonce",i),n.insertBefore(o,s),o},Ae=function(){function e(e){this.element=Ie(e),this.element.appendChild(document.createTextNode("")),this.sheet=function(e){if(e.sheet)return e.sheet;for(var t=document.styleSheets,n=0,o=t.length;n<o;n++){var r=t[n];if(r.ownerNode===e)return r}throw he(17)}(this.element),this.length=0}return e.prototype.insertRule=function(e,t){try{return this.sheet.insertRule(t,e),this.length++,!0}catch(e){return!1}},e.prototype.deleteRule=function(e){this.sheet.deleteRule(e),this.length--},e.prototype.getRule=function(e){var t=this.sheet.cssRules[e];return t&&t.cssText?t.cssText:""},e}(),Oe=function(){function e(e){this.element=Ie(e),this.nodes=this.element.childNodes,this.length=0}return e.prototype.insertRule=function(e,t){if(e<=this.length&&e>=0){var n=document.createTextNode(t);return this.element.insertBefore(n,this.nodes[e]||null),this.length++,!0}return!1},e.prototype.deleteRule=function(e){this.element.removeChild(this.nodes[e]),this.length--},e.prototype.getRule=function(e){return e<this.length?this.nodes[e].textContent:""},e}(),De=function(){function e(e){this.rules=[],this.length=0}return e.prototype.insertRule=function(e,t){return e<=this.length&&(this.rules.splice(e,0,t),this.length++,!0)},e.prototype.deleteRule=function(e){this.rules.splice(e,1),this.length--},e.prototype.getRule=function(e){return e<this.length?this.rules[e]:""},e}(),Re=S,Te={isServer:!S,useCSSOMInjection:!w},ke=function(){function e(e,n,o){void 0===e&&(e=C),void 0===n&&(n={});var r=this;this.options=(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)((0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)({},Te),e),this.gs=n,this.names=new Map(o),this.server=!!e.isServer,!this.server&&S&&Re&&(Re=!1,_e(this)),ue(this,function(){return function(e){for(var t=e.getTag(),n=t.length,o="",r=function(n){var r=function(e){return ve.get(e)}(n);if(void 0===r)return"continue";var s=e.names.get(r),i=t.getGroup(n);if(void 0===s||!s.size||0===i.length)return"continue";var a="".concat(f,".g").concat(n,'[id="').concat(r,'"]'),c="";void 0!==s&&s.forEach(function(e){e.length>0&&(c+="".concat(e,","))}),o+="".concat(i).concat(a,'{content:"').concat(c,'"}').concat(g)},s=0;s<n;s++)r(s);return o}(r)})}return e.registerId=function(e){return Se(e)},e.prototype.rehydrate=function(){!this.server&&S&&_e(this)},e.prototype.reconstructWithOptions=function(n,o){return void 0===o&&(o=!0),new e((0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)((0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)({},this.options),n),this.gs,o&&this.names||void 0)},e.prototype.allocateGSInstance=function(e){return this.gs[e]=(this.gs[e]||0)+1},e.prototype.getTag=function(){return this.tag||(this.tag=(e=function(e){var t=e.useCSSOMInjection,n=e.target;return e.isServer?new De(n):t?new Ae(n):new Oe(n)}(this.options),new fe(e)));// removed by dead control flow
 var e; },e.prototype.hasNameForId=function(e,t){return this.names.has(e)&&this.names.get(e).has(t)},e.prototype.registerName=function(e,t){if(Se(e),this.names.has(e))this.names.get(e).add(t);else{var n=new Set;n.add(t),this.names.set(e,n)}},e.prototype.insertRules=function(e,t,n){this.registerName(e,t),this.getTag().insertRules(Se(e),n)},e.prototype.clearNames=function(e){this.names.has(e)&&this.names.get(e).clear()},e.prototype.clearRules=function(e){this.getTag().clearGroup(Se(e)),this.clearNames(e)},e.prototype.clearTag=function(){this.tag=void 0},e}(),je=/&/g,xe=/^\s*\/\/.*$/gm;function Ve(e,t){return e.map(function(e){return"rule"===e.type&&(e.value="".concat(t," ").concat(e.value),e.value=e.value.replaceAll(",",",".concat(t," ")),e.props=e.props.map(function(e){return"".concat(t," ").concat(e)})),Array.isArray(e.children)&&"@keyframes"!==e.type&&(e.children=Ve(e.children,t)),e})}function Fe(e){var t,n,o,r=void 0===e?C:e,s=r.options,i=void 0===s?C:s,a=r.plugins,c=void 0===a?_:a,l=function(e,o,r){return r.startsWith(n)&&r.endsWith(n)&&r.replaceAll(n,"").length>0?".".concat(t):e},u=c.slice();u.push(function(e){e.type===stylis__WEBPACK_IMPORTED_MODULE_4__.RULESET&&e.value.includes("&")&&(e.props[0]=e.props[0].replace(je,n).replace(o,l))}),i.prefix&&u.push(stylis__WEBPACK_IMPORTED_MODULE_7__.prefixer),u.push(stylis__WEBPACK_IMPORTED_MODULE_6__.stringify);var p=function(e,r,s,a){void 0===r&&(r=""),void 0===s&&(s=""),void 0===a&&(a="&"),t=a,n=r,o=new RegExp("\\".concat(n,"\\b"),"g");var c=e.replace(xe,""),l=stylis__WEBPACK_IMPORTED_MODULE_5__.compile(s||r?"".concat(s," ").concat(r," { ").concat(c," }"):c);i.namespace&&(l=Ve(l,i.namespace));var p=[];return stylis__WEBPACK_IMPORTED_MODULE_6__.serialize(l,stylis__WEBPACK_IMPORTED_MODULE_7__.middleware(u.concat(stylis__WEBPACK_IMPORTED_MODULE_7__.rulesheet(function(e){return p.push(e)})))),p};return p.hash=c.length?c.reduce(function(e,t){return t.name||he(15),M(e,t.name)},F).toString():"",p}var Me=new ke,ze=Fe(),$e=react__WEBPACK_IMPORTED_MODULE_2___default().createContext({shouldForwardProp:void 0,styleSheet:Me,stylis:ze}),Be=$e.Consumer,Le=react__WEBPACK_IMPORTED_MODULE_2___default().createContext(void 0);function Ge(){return (0,react__WEBPACK_IMPORTED_MODULE_2__.useContext)($e)}function Ye(e){var t=(0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(e.stylisPlugins),n=t[0],r=t[1],c=Ge().styleSheet,l=(0,react__WEBPACK_IMPORTED_MODULE_2__.useMemo)(function(){var t=c;return e.sheet?t=e.sheet:e.target&&(t=t.reconstructWithOptions({target:e.target},!1)),e.disableCSSOMInjection&&(t=t.reconstructWithOptions({useCSSOMInjection:!1})),t},[e.disableCSSOMInjection,e.sheet,e.target,c]),u=(0,react__WEBPACK_IMPORTED_MODULE_2__.useMemo)(function(){return Fe({options:{namespace:e.namespace,prefix:e.enableVendorPrefixes},plugins:n})},[e.enableVendorPrefixes,e.namespace,n]);(0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(function(){shallowequal__WEBPACK_IMPORTED_MODULE_3___default()(n,e.stylisPlugins)||r(e.stylisPlugins)},[e.stylisPlugins]);var d=(0,react__WEBPACK_IMPORTED_MODULE_2__.useMemo)(function(){return{shouldForwardProp:e.shouldForwardProp,styleSheet:l,stylis:u}},[e.shouldForwardProp,l,u]);return react__WEBPACK_IMPORTED_MODULE_2___default().createElement($e.Provider,{value:d},react__WEBPACK_IMPORTED_MODULE_2___default().createElement(Le.Provider,{value:u},e.children))}var We=function(){function e(e,t){var n=this;this.inject=function(e,t){void 0===t&&(t=ze);var o=n.name+t.hash;e.hasNameForId(n.id,o)||e.insertRules(n.id,o,t(n.rules,o,"@keyframes"))},this.name=e,this.id="sc-keyframes-".concat(e),this.rules=t,ue(this,function(){throw he(12,String(n.name))})}return e.prototype.getName=function(e){return void 0===e&&(e=ze),this.name+e.hash},e}(),qe=function(e){return e>="A"&&e<="Z"};function He(e){for(var t="",n=0;n<e.length;n++){var o=e[n];if(1===n&&"-"===o&&"-"===e[0])return e;qe(o)?t+="-"+o.toLowerCase():t+=o}return t.startsWith("ms-")?"-"+t:t}var Ue=function(e){return null==e||!1===e||""===e},Je=function(t){var n,o,r=[];for(var s in t){var i=t[s];t.hasOwnProperty(s)&&!Ue(i)&&(Array.isArray(i)&&i.isCss||re(i)?r.push("".concat(He(s),":"),i,";"):ce(i)?r.push.apply(r,(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__spreadArray)((0,tslib__WEBPACK_IMPORTED_MODULE_0__.__spreadArray)(["".concat(s," {")],Je(i),!1),["}"],!1)):r.push("".concat(He(s),": ").concat((n=s,null==(o=i)||"boolean"==typeof o||""===o?"":"number"!=typeof o||0===o||n in _emotion_unitless__WEBPACK_IMPORTED_MODULE_8__["default"]||n.startsWith("--")?String(o).trim():"".concat(o,"px")),";")))}return r};function Xe(e,t,n,o){if(Ue(e))return[];if(se(e))return[".".concat(e.styledComponentId)];if(re(e)){if(!re(s=e)||s.prototype&&s.prototype.isReactComponent||!t)return[e];var r=e(t);return false||"object"!=typeof r||Array.isArray(r)||r instanceof We||ce(r)||null===r||console.error("".concat(B(e)," is not a styled component and cannot be referred to via component selector. See https://www.styled-components.com/docs/advanced#referring-to-other-components for more details.")),Xe(r,t,n,o)}var s;return e instanceof We?n?(e.inject(n,o),[e.getName(o)]):[e]:ce(e)?Je(e):Array.isArray(e)?Array.prototype.concat.apply(_,e.map(function(e){return Xe(e,t,n,o)})):[e.toString()]}function Ze(e){for(var t=0;t<e.length;t+=1){var n=e[t];if(re(n)&&!se(n))return!1}return!0}var Ke=z(v),Qe=function(){function e(e,t,n){this.rules=e,this.staticRulesId="",this.isStatic= false&&0,this.componentId=t,this.baseHash=M(Ke,t),this.baseStyle=n,ke.registerId(t)}return e.prototype.generateAndInjectStyles=function(e,t,n){var o=this.baseStyle?this.baseStyle.generateAndInjectStyles(e,t,n):"";if(this.isStatic&&!n.hash)if(this.staticRulesId&&t.hasNameForId(this.componentId,this.staticRulesId))o=ie(o,this.staticRulesId);else{var r=ae(Xe(this.rules,e,t,n)),s=x(M(this.baseHash,r)>>>0);if(!t.hasNameForId(this.componentId,s)){var i=n(r,".".concat(s),void 0,this.componentId);t.insertRules(this.componentId,s,i)}o=ie(o,s),this.staticRulesId=s}else{for(var a=M(this.baseHash,n.hash),c="",l=0;l<this.rules.length;l++){var u=this.rules[l];if("string"==typeof u)c+=u, true&&(a=M(a,u));else if(u){var p=ae(Xe(u,e,t,n));a=M(a,p+l),c+=p}}if(c){var d=x(a>>>0);t.hasNameForId(this.componentId,d)||t.insertRules(this.componentId,d,n(c,".".concat(d),void 0,this.componentId)),o=ie(o,d)}}return o},e}(),et=react__WEBPACK_IMPORTED_MODULE_2___default().createContext(void 0),tt=et.Consumer;function nt(){var e=(0,react__WEBPACK_IMPORTED_MODULE_2__.useContext)(et);if(!e)throw he(18);return e}function ot(e){var n=react__WEBPACK_IMPORTED_MODULE_2___default().useContext(et),r=(0,react__WEBPACK_IMPORTED_MODULE_2__.useMemo)(function(){return function(e,n){if(!e)throw he(14);if(re(e)){var o=e(n);if( true&&(null===o||Array.isArray(o)||"object"!=typeof o))throw he(7);return o}if(Array.isArray(e)||"object"!=typeof e)throw he(8);return n?(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)((0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)({},n),e):e}(e.theme,n)},[e.theme,n]);return e.children?react__WEBPACK_IMPORTED_MODULE_2___default().createElement(et.Provider,{value:r},e.children):null}var rt={},st=new Set;function it(e,r,s){var i=se(e),a=e,c=!L(e),p=r.attrs,d=void 0===p?_:p,h=r.componentId,f=void 0===h?function(e,t){var n="string"!=typeof e?"sc":R(e);rt[n]=(rt[n]||0)+1;var o="".concat(n,"-").concat($(v+n+rt[n]));return t?"".concat(t,"-").concat(o):o}(r.displayName,r.parentComponentId):h,m=r.displayName,y=void 0===m?function(e){return L(e)?"styled.".concat(e):"Styled(".concat(B(e),")")}(e):m,g=r.displayName&&r.componentId?"".concat(R(r.displayName),"-").concat(r.componentId):r.componentId||f,S=i&&a.attrs?a.attrs.concat(d).filter(Boolean):d,w=r.shouldForwardProp;if(i&&a.shouldForwardProp){var b=a.shouldForwardProp;if(r.shouldForwardProp){var E=r.shouldForwardProp;w=function(e,t){return b(e,t)&&E(e,t)}}else w=b}var N=new Qe(s,g,i?a.componentStyle:void 0);function O(e,r){return function(e,r,s){var i=e.attrs,a=e.componentStyle,c=e.defaultProps,p=e.foldedComponentIds,d=e.styledComponentId,h=e.target,f=react__WEBPACK_IMPORTED_MODULE_2___default().useContext(et),m=Ge(),y=e.shouldForwardProp||m.shouldForwardProp; true&&(0,react__WEBPACK_IMPORTED_MODULE_2__.useDebugValue)(d);var v=I(r,f,c)||C,g=function(e,n,o){for(var r,s=(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)((0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)({},n),{className:void 0,theme:o}),i=0;i<e.length;i+=1){var a=re(r=e[i])?r(s):r;for(var c in a)s[c]="className"===c?ie(s[c],a[c]):"style"===c?(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)((0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)({},s[c]),a[c]):a[c]}return n.className&&(s.className=ie(s.className,n.className)),s}(i,r,v),S=g.as||h,w={};for(var b in g)void 0===g[b]||"$"===b[0]||"as"===b||"theme"===b&&g.theme===v||("forwardedAs"===b?w.as=g.forwardedAs:y&&!y(b,S)||(w[b]=g[b],y||"development"!=="development"||(0,_emotion_is_prop_valid__WEBPACK_IMPORTED_MODULE_1__["default"])(b)||st.has(b)||!A.has(S)||(st.add(b),console.warn('styled-components: it looks like an unknown prop "'.concat(b,'" is being sent through to the DOM, which will likely trigger a React console error. If you would like automatic filtering of unknown props, you can opt-into that behavior via `<StyleSheetManager shouldForwardProp={...}>` (connect an API like `@emotion/is-prop-valid`) or consider using transient props (`$` prefix for automatic filtering.)')))));var E=function(e,t){var n=Ge(),o=e.generateAndInjectStyles(t,n.styleSheet,n.stylis);return true&&(0,react__WEBPACK_IMPORTED_MODULE_2__.useDebugValue)(o),o}(a,g); true&&e.warnTooManyClasses&&e.warnTooManyClasses(E);var N=ie(p,d);return E&&(N+=" "+E),g.className&&(N+=" "+g.className),w[L(S)&&!A.has(S)?"class":"className"]=N,w.ref=s,(0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(S,w)}(D,e,r)}O.displayName=y;var D=react__WEBPACK_IMPORTED_MODULE_2___default().forwardRef(O);return D.attrs=S,D.componentStyle=N,D.displayName=y,D.shouldForwardProp=w,D.foldedComponentIds=i?ie(a.foldedComponentIds,a.styledComponentId):"",D.styledComponentId=g,D.target=i?a.target:e,Object.defineProperty(D,"defaultProps",{get:function(){return this._foldedDefaultProps},set:function(e){this._foldedDefaultProps=i?function(e){for(var t=[],n=1;n<arguments.length;n++)t[n-1]=arguments[n];for(var o=0,r=t;o<r.length;o++)le(e,r[o],!0);return e}({},a.defaultProps,e):e}}), true&&(P(y,g),D.warnTooManyClasses=function(e,t){var n={},o=!1;return function(r){if(!o&&(n[r]=!0,Object.keys(n).length>=200)){var s=t?' with the id of "'.concat(t,'"'):"";console.warn("Over ".concat(200," classes were generated for component ").concat(e).concat(s,".\n")+"Consider using the attrs method, together with a style object for frequently changed styles.\nExample:\n  const Component = styled.div.attrs(props => ({\n    style: {\n      background: props.background,\n    },\n  }))`width: 100%;`\n\n  <Component />"),o=!0,n={}}}}(y,g)),ue(D,function(){return".".concat(D.styledComponentId)}),c&&oe(D,e,{attrs:!0,componentStyle:!0,displayName:!0,foldedComponentIds:!0,shouldForwardProp:!0,styledComponentId:!0,target:!0}),D}function at(e,t){for(var n=[e[0]],o=0,r=t.length;o<r;o+=1)n.push(t[o],e[o+1]);return n}var ct=function(e){return Object.assign(e,{isCss:!0})};function lt(t){for(var n=[],o=1;o<arguments.length;o++)n[o-1]=arguments[o];if(re(t)||ce(t))return ct(Xe(at(_,(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__spreadArray)([t],n,!0))));var r=t;return 0===n.length&&1===r.length&&"string"==typeof r[0]?Xe(r):ct(Xe(at(r,n)))}function ut(n,o,r){if(void 0===r&&(r=C),!o)throw he(1,o);var s=function(t){for(var s=[],i=1;i<arguments.length;i++)s[i-1]=arguments[i];return n(o,r,lt.apply(void 0,(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__spreadArray)([t],s,!1)))};return s.attrs=function(e){return ut(n,o,(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)((0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)({},r),{attrs:Array.prototype.concat(r.attrs,e).filter(Boolean)}))},s.withConfig=function(e){return ut(n,o,(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)((0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)({},r),e))},s}var pt=function(e){return ut(it,e)},dt=pt;A.forEach(function(e){dt[e]=pt(e)});var ht=function(){function e(e,t){this.rules=e,this.componentId=t,this.isStatic=Ze(e),ke.registerId(this.componentId+1)}return e.prototype.createStyles=function(e,t,n,o){var r=o(ae(Xe(this.rules,t,n,o)),""),s=this.componentId+e;n.insertRules(s,s,r)},e.prototype.removeStyles=function(e,t){t.clearRules(this.componentId+e)},e.prototype.renderStyles=function(e,t,n,o){e>2&&ke.registerId(this.componentId+e),this.removeStyles(e,n),this.createStyles(e,t,n,o)},e}();function ft(n){for(var r=[],s=1;s<arguments.length;s++)r[s-1]=arguments[s];var i=lt.apply(void 0,(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__spreadArray)([n],r,!1)),a="sc-global-".concat($(JSON.stringify(i))),c=new ht(i,a); true&&P(a);var l=function(e){var t=Ge(),n=react__WEBPACK_IMPORTED_MODULE_2___default().useContext(et),r=react__WEBPACK_IMPORTED_MODULE_2___default().useRef(t.styleSheet.allocateGSInstance(a)).current;return true&&react__WEBPACK_IMPORTED_MODULE_2___default().Children.count(e.children)&&console.warn("The global style component ".concat(a," was given child JSX. createGlobalStyle does not render children.")), true&&i.some(function(e){return"string"==typeof e&&-1!==e.indexOf("@import")})&&console.warn("Please do not use @import CSS syntax in createGlobalStyle at this time, as the CSSOM APIs we use in production do not handle it well. Instead, we recommend using a library such as react-helmet to inject a typical <link> meta tag to the stylesheet, or simply embedding it manually in your index.html <head> section for a simpler app."),t.styleSheet.server&&u(r,e,t.styleSheet,n,t.stylis),react__WEBPACK_IMPORTED_MODULE_2___default().useLayoutEffect(function(){if(!t.styleSheet.server)return u(r,e,t.styleSheet,n,t.stylis),function(){return c.removeStyles(r,t.styleSheet)}},[r,e,t.styleSheet,n,t.stylis]),null};function u(e,n,o,r,s){if(c.isStatic)c.renderStyles(e,b,o,s);else{var i=(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)((0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)({},n),{theme:I(n,r,l.defaultProps)});c.renderStyles(e,i,o,s)}}return react__WEBPACK_IMPORTED_MODULE_2___default().memo(l)}function mt(t){for(var n=[],o=1;o<arguments.length;o++)n[o-1]=arguments[o]; true&&"undefined"!=typeof navigator&&"ReactNative"===navigator.product&&console.warn("`keyframes` cannot be used on ReactNative, only on the web. To do animation in ReactNative please use Animated.");var r=ae(lt.apply(void 0,(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__spreadArray)([t],n,!1))),s=$(r);return new We(s,r)}function yt(e){var n=react__WEBPACK_IMPORTED_MODULE_2___default().forwardRef(function(n,r){var s=I(n,react__WEBPACK_IMPORTED_MODULE_2___default().useContext(et),e.defaultProps);return true&&void 0===s&&console.warn('[withTheme] You are not using a ThemeProvider nor passing a theme prop or a theme in defaultProps in component class "'.concat(B(e),'"')),react__WEBPACK_IMPORTED_MODULE_2___default().createElement(e,(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)({},n,{theme:s,ref:r}))});return n.displayName="WithTheme(".concat(B(e),")"),oe(n,e)}var vt=function(){function e(){var e=this;this._emitSheetCSS=function(){var t=e.instance.toString();if(!t)return"";var n=Ce(),o=ae([n&&'nonce="'.concat(n,'"'),"".concat(f,'="true"'),"".concat(y,'="').concat(v,'"')].filter(Boolean)," ");return"<style ".concat(o,">").concat(t,"</style>")},this.getStyleTags=function(){if(e.sealed)throw he(2);return e._emitSheetCSS()},this.getStyleElement=function(){var n;if(e.sealed)throw he(2);var r=e.instance.toString();if(!r)return[];var s=((n={})[f]="",n[y]=v,n.dangerouslySetInnerHTML={__html:r},n),i=Ce();return i&&(s.nonce=i),[react__WEBPACK_IMPORTED_MODULE_2___default().createElement("style",(0,tslib__WEBPACK_IMPORTED_MODULE_0__.__assign)({},s,{key:"sc-0-0"}))]},this.seal=function(){e.sealed=!0},this.instance=new ke({isServer:!0}),this.sealed=!1}return e.prototype.collectStyles=function(e){if(this.sealed)throw he(2);return react__WEBPACK_IMPORTED_MODULE_2___default().createElement(Ye,{sheet:this.instance},e)},e.prototype.interleaveWithNodeStream=function(e){throw he(3)},e}(),gt={StyleSheet:ke,mainSheet:Me}; true&&"undefined"!=typeof navigator&&"ReactNative"===navigator.product&&console.warn("It looks like you've imported 'styled-components' on React Native.\nPerhaps you're looking to import 'styled-components/native'?\nRead more about this at https://www.styled-components.com/docs/basics#react-native");var St="__sc-".concat(f,"__"); true&&"undefined"!=typeof window&&(window[St]||(window[St]=0),1===window[St]&&console.warn("It looks like there are several instances of 'styled-components' initialized in this application. This may cause dynamic styles to not render properly, errors during the rehydration process, a missing theme prop, and makes your application bigger without good reason.\n\nSee https://s-c.sh/2BAXzed for more info."),window[St]+=1);
//# sourceMappingURL=styled-components.browser.esm.js.map


/***/ }),

/***/ "./node_modules/styled-components/node_modules/stylis/src/Enum.js":
/*!************************************************************************!*\
  !*** ./node_modules/styled-components/node_modules/stylis/src/Enum.js ***!
  \************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CHARSET: () => (/* binding */ CHARSET),
/* harmony export */   COMMENT: () => (/* binding */ COMMENT),
/* harmony export */   COUNTER_STYLE: () => (/* binding */ COUNTER_STYLE),
/* harmony export */   DECLARATION: () => (/* binding */ DECLARATION),
/* harmony export */   DOCUMENT: () => (/* binding */ DOCUMENT),
/* harmony export */   FONT_FACE: () => (/* binding */ FONT_FACE),
/* harmony export */   FONT_FEATURE_VALUES: () => (/* binding */ FONT_FEATURE_VALUES),
/* harmony export */   IMPORT: () => (/* binding */ IMPORT),
/* harmony export */   KEYFRAMES: () => (/* binding */ KEYFRAMES),
/* harmony export */   LAYER: () => (/* binding */ LAYER),
/* harmony export */   MEDIA: () => (/* binding */ MEDIA),
/* harmony export */   MOZ: () => (/* binding */ MOZ),
/* harmony export */   MS: () => (/* binding */ MS),
/* harmony export */   NAMESPACE: () => (/* binding */ NAMESPACE),
/* harmony export */   PAGE: () => (/* binding */ PAGE),
/* harmony export */   RULESET: () => (/* binding */ RULESET),
/* harmony export */   SCOPE: () => (/* binding */ SCOPE),
/* harmony export */   SUPPORTS: () => (/* binding */ SUPPORTS),
/* harmony export */   VIEWPORT: () => (/* binding */ VIEWPORT),
/* harmony export */   WEBKIT: () => (/* binding */ WEBKIT)
/* harmony export */ });
var MS = '-ms-'
var MOZ = '-moz-'
var WEBKIT = '-webkit-'

var COMMENT = 'comm'
var RULESET = 'rule'
var DECLARATION = 'decl'

var PAGE = '@page'
var MEDIA = '@media'
var IMPORT = '@import'
var CHARSET = '@charset'
var VIEWPORT = '@viewport'
var SUPPORTS = '@supports'
var DOCUMENT = '@document'
var NAMESPACE = '@namespace'
var KEYFRAMES = '@keyframes'
var FONT_FACE = '@font-face'
var COUNTER_STYLE = '@counter-style'
var FONT_FEATURE_VALUES = '@font-feature-values'
var LAYER = '@layer'
var SCOPE = '@scope'


/***/ }),

/***/ "./node_modules/styled-components/node_modules/stylis/src/Middleware.js":
/*!******************************************************************************!*\
  !*** ./node_modules/styled-components/node_modules/stylis/src/Middleware.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   middleware: () => (/* binding */ middleware),
/* harmony export */   namespace: () => (/* binding */ namespace),
/* harmony export */   prefixer: () => (/* binding */ prefixer),
/* harmony export */   rulesheet: () => (/* binding */ rulesheet)
/* harmony export */ });
/* harmony import */ var _Enum_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Enum.js */ "./node_modules/styled-components/node_modules/stylis/src/Enum.js");
/* harmony import */ var _Utility_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Utility.js */ "./node_modules/styled-components/node_modules/stylis/src/Utility.js");
/* harmony import */ var _Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Tokenizer.js */ "./node_modules/styled-components/node_modules/stylis/src/Tokenizer.js");
/* harmony import */ var _Serializer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Serializer.js */ "./node_modules/styled-components/node_modules/stylis/src/Serializer.js");
/* harmony import */ var _Prefixer_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Prefixer.js */ "./node_modules/styled-components/node_modules/stylis/src/Prefixer.js");






/**
 * @param {function[]} collection
 * @return {function}
 */
function middleware (collection) {
	var length = (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.sizeof)(collection)

	return function (element, index, children, callback) {
		var output = ''

		for (var i = 0; i < length; i++)
			output += collection[i](element, index, children, callback) || ''

		return output
	}
}

/**
 * @param {function} callback
 * @return {function}
 */
function rulesheet (callback) {
	return function (element) {
		if (!element.root)
			if (element = element.return)
				callback(element)
	}
}

/**
 * @param {object} element
 * @param {number} index
 * @param {object[]} children
 * @param {function} callback
 */
function prefixer (element, index, children, callback) {
	if (element.length > -1)
		if (!element.return)
			switch (element.type) {
				case _Enum_js__WEBPACK_IMPORTED_MODULE_0__.DECLARATION: element.return = (0,_Prefixer_js__WEBPACK_IMPORTED_MODULE_4__.prefix)(element.value, element.length, children)
					return
				case _Enum_js__WEBPACK_IMPORTED_MODULE_0__.KEYFRAMES:
					return (0,_Serializer_js__WEBPACK_IMPORTED_MODULE_3__.serialize)([(0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.copy)(element, {value: (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(element.value, '@', '@' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT)})], callback)
				case _Enum_js__WEBPACK_IMPORTED_MODULE_0__.RULESET:
					if (element.length)
						return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.combine)(children = element.props, function (value) {
							switch ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.match)(value, callback = /(::plac\w+|:read-\w+)/)) {
								// :read-(only|write)
								case ':read-only': case ':read-write':
									(0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.lift)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.copy)(element, {props: [(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /:(read-\w+)/, ':' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MOZ + '$1')]}))
									;(0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.lift)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.copy)(element, {props: [value]}))
									;(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.assign)(element, {props: (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.filter)(children, callback)})
									break
								// :placeholder
								case '::placeholder':
									;(0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.lift)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.copy)(element, {props: [(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /:(plac\w+)/, ':' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + 'input-$1')]}))
									;(0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.lift)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.copy)(element, {props: [(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /:(plac\w+)/, ':' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MOZ + '$1')]}))
									;(0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.lift)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.copy)(element, {props: [(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /:(plac\w+)/, _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + 'input-$1')]}))
									;(0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.lift)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.copy)(element, {props: [value]}))
									;(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.assign)(element, {props: (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.filter)(children, callback)})
									break
							}

							return ''
						})
			}
}

/**
 * @param {object} element
 * @param {number} index
 * @param {object[]} children
 */
function namespace (element) {
	switch (element.type) {
		case _Enum_js__WEBPACK_IMPORTED_MODULE_0__.RULESET:
			element.props = element.props.map(function (value) {
				return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.combine)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.tokenize)(value), function (value, index, children) {
					switch ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(value, 0)) {
						// \f
						case 12:
							return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.substr)(value, 1, (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.strlen)(value))
						// \0 ( + > ~
						case 0: case 40: case 43: case 62: case 126:
							return value
						// :
						case 58:
							if (children[++index] === 'global')
								children[index] = '', children[++index] = '\f' + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.substr)(children[index], index = 1, -1)
						// \s
						case 32:
							return index === 1 ? '' : value
						default:
							switch (index) {
								case 0: element = value
									return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.sizeof)(children) > 1 ? '' : value
								case index = (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.sizeof)(children) - 1: case 2:
									return index === 2 ? value + element + element : value + element
								default:
									return value
							}
					}
				})
			})
	}
}


/***/ }),

/***/ "./node_modules/styled-components/node_modules/stylis/src/Parser.js":
/*!**************************************************************************!*\
  !*** ./node_modules/styled-components/node_modules/stylis/src/Parser.js ***!
  \**************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   comment: () => (/* binding */ comment),
/* harmony export */   compile: () => (/* binding */ compile),
/* harmony export */   declaration: () => (/* binding */ declaration),
/* harmony export */   parse: () => (/* binding */ parse),
/* harmony export */   ruleset: () => (/* binding */ ruleset)
/* harmony export */ });
/* harmony import */ var _Enum_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Enum.js */ "./node_modules/styled-components/node_modules/stylis/src/Enum.js");
/* harmony import */ var _Utility_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Utility.js */ "./node_modules/styled-components/node_modules/stylis/src/Utility.js");
/* harmony import */ var _Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Tokenizer.js */ "./node_modules/styled-components/node_modules/stylis/src/Tokenizer.js");




/**
 * @param {string} value
 * @return {object[]}
 */
function compile (value) {
	return (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.dealloc)(parse('', null, null, null, [''], value = (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.alloc)(value), 0, [0], value))
}

/**
 * @param {string} value
 * @param {object} root
 * @param {object?} parent
 * @param {string[]} rule
 * @param {string[]} rules
 * @param {string[]} rulesets
 * @param {number[]} pseudo
 * @param {number[]} points
 * @param {string[]} declarations
 * @return {object}
 */
function parse (value, root, parent, rule, rules, rulesets, pseudo, points, declarations) {
	var index = 0
	var offset = 0
	var length = pseudo
	var atrule = 0
	var property = 0
	var previous = 0
	var variable = 1
	var scanning = 1
	var ampersand = 1
	var character = 0
	var type = ''
	var props = rules
	var children = rulesets
	var reference = rule
	var characters = type

	while (scanning)
		switch (previous = character, character = (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.next)()) {
			// (
			case 40:
				if (previous != 108 && (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(characters, length - 1) == 58) {
					if ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.indexof)(characters += (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.delimit)(character), '&', '&\f'), '&\f', (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.abs)(index ? points[index - 1] : 0)) != -1)
						ampersand = -1
					break
				}
			// " ' [
			case 34: case 39: case 91:
				characters += (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.delimit)(character)
				break
			// \t \n \r \s
			case 9: case 10: case 13: case 32:
				characters += (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.whitespace)(previous)
				break
			// \
			case 92:
				characters += (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.escaping)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.caret)() - 1, 7)
				continue
			// /
			case 47:
				switch ((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.peek)()) {
					case 42: case 47:
						;(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.append)(comment((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.commenter)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.next)(), (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.caret)()), root, parent, declarations), declarations)
						break
					default:
						characters += '/'
				}
				break
			// {
			case 123 * variable:
				points[index++] = (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.strlen)(characters) * ampersand
			// } ; \0
			case 125 * variable: case 59: case 0:
				switch (character) {
					// \0 }
					case 0: case 125: scanning = 0
					// ;
					case 59 + offset: if (ampersand == -1) characters = (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(characters, /\f/g, '')
						if (property > 0 && ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.strlen)(characters) - length))
							(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.append)(property > 32 ? declaration(characters + ';', rule, parent, length - 1, declarations) : declaration((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(characters, ' ', '') + ';', rule, parent, length - 2, declarations), declarations)
						break
					// @ ;
					case 59: characters += ';'
					// { rule/at-rule
					default:
						;(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.append)(reference = ruleset(characters, root, parent, index, offset, rules, points, type, props = [], children = [], length, rulesets), rulesets)

						if (character === 123)
							if (offset === 0)
								parse(characters, root, reference, reference, props, rulesets, length, points, children)
							else
								switch (atrule === 99 && (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(characters, 3) === 110 ? 100 : atrule) {
									// d l m s
									case 100: case 108: case 109: case 115:
										parse(value, reference, reference, rule && (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.append)(ruleset(value, reference, reference, 0, 0, rules, points, type, rules, props = [], length, children), children), rules, children, length, points, rule ? props : children)
										break
									default:
										parse(characters, reference, reference, reference, [''], children, 0, points, children)
								}
				}

				index = offset = property = 0, variable = ampersand = 1, type = characters = '', length = pseudo
				break
			// :
			case 58:
				length = 1 + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.strlen)(characters), property = previous
			default:
				if (variable < 1)
					if (character == 123)
						--variable
					else if (character == 125 && variable++ == 0 && (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.prev)() == 125)
						continue

				switch (characters += (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.from)(character), character * variable) {
					// &
					case 38:
						ampersand = offset > 0 ? 1 : (characters += '\f', -1)
						break
					// ,
					case 44:
						points[index++] = ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.strlen)(characters) - 1) * ampersand, ampersand = 1
						break
					// @
					case 64:
						// -
						if ((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.peek)() === 45)
							characters += (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.delimit)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.next)())

						atrule = (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.peek)(), offset = length = (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.strlen)(type = characters += (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.identifier)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.caret)())), character++
						break
					// -
					case 45:
						if (previous === 45 && (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.strlen)(characters) == 2)
							variable = 0
				}
		}

	return rulesets
}

/**
 * @param {string} value
 * @param {object} root
 * @param {object?} parent
 * @param {number} index
 * @param {number} offset
 * @param {string[]} rules
 * @param {number[]} points
 * @param {string} type
 * @param {string[]} props
 * @param {string[]} children
 * @param {number} length
 * @param {object[]} siblings
 * @return {object}
 */
function ruleset (value, root, parent, index, offset, rules, points, type, props, children, length, siblings) {
	var post = offset - 1
	var rule = offset === 0 ? rules : ['']
	var size = (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.sizeof)(rule)

	for (var i = 0, j = 0, k = 0; i < index; ++i)
		for (var x = 0, y = (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.substr)(value, post + 1, post = (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.abs)(j = points[i])), z = value; x < size; ++x)
			if (z = (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.trim)(j > 0 ? rule[x] + ' ' + y : (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(y, /&\f/g, rule[x])))
				props[k++] = z

	return (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.node)(value, root, parent, offset === 0 ? _Enum_js__WEBPACK_IMPORTED_MODULE_0__.RULESET : type, props, children, length, siblings)
}

/**
 * @param {number} value
 * @param {object} root
 * @param {object?} parent
 * @param {object[]} siblings
 * @return {object}
 */
function comment (value, root, parent, siblings) {
	return (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.node)(value, root, parent, _Enum_js__WEBPACK_IMPORTED_MODULE_0__.COMMENT, (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.from)((0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.char)()), (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.substr)(value, 2, -2), 0, siblings)
}

/**
 * @param {string} value
 * @param {object} root
 * @param {object?} parent
 * @param {number} length
 * @param {object[]} siblings
 * @return {object}
 */
function declaration (value, root, parent, length, siblings) {
	return (0,_Tokenizer_js__WEBPACK_IMPORTED_MODULE_2__.node)(value, root, parent, _Enum_js__WEBPACK_IMPORTED_MODULE_0__.DECLARATION, (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.substr)(value, 0, length), (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.substr)(value, length + 1, -1), length, siblings)
}


/***/ }),

/***/ "./node_modules/styled-components/node_modules/stylis/src/Prefixer.js":
/*!****************************************************************************!*\
  !*** ./node_modules/styled-components/node_modules/stylis/src/Prefixer.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   prefix: () => (/* binding */ prefix)
/* harmony export */ });
/* harmony import */ var _Enum_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Enum.js */ "./node_modules/styled-components/node_modules/stylis/src/Enum.js");
/* harmony import */ var _Utility_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Utility.js */ "./node_modules/styled-components/node_modules/stylis/src/Utility.js");



/**
 * @param {string} value
 * @param {number} length
 * @param {object[]} children
 * @return {string}
 */
function prefix (value, length, children) {
	switch ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.hash)(value, length)) {
		// color-adjust
		case 5103:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + 'print-' + value + value
		// animation, animation-(delay|direction|duration|fill-mode|iteration-count|name|play-state|timing-function)
		case 5737: case 4201: case 3177: case 3433: case 1641: case 4457: case 2921:
		// text-decoration, filter, clip-path, backface-visibility, column, box-decoration-break
		case 5572: case 6356: case 5844: case 3191: case 6645: case 3005:
		// mask, mask-image, mask-(mode|clip|size), mask-(repeat|origin), mask-position, mask-composite,
		case 6391: case 5879: case 5623: case 6135: case 4599: case 4855:
		// background-clip, columns, column-(count|fill|gap|rule|rule-color|rule-style|rule-width|span|width)
		case 4215: case 6389: case 5109: case 5365: case 5621: case 3829:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + value
		// tab-size
		case 4789:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MOZ + value + value
		// appearance, user-select, transform, hyphens, text-size-adjust
		case 5349: case 4246: case 4810: case 6968: case 2756:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MOZ + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + value + value
		// writing-mode
		case 5936:
			switch ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(value, length + 11)) {
				// vertical-l(r)
				case 114:
					return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /[svh]\w+-[tblr]{2}/, 'tb') + value
				// vertical-r(l)
				case 108:
					return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /[svh]\w+-[tblr]{2}/, 'tb-rl') + value
				// horizontal(-)tb
				case 45:
					return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /[svh]\w+-[tblr]{2}/, 'lr') + value
				// default: fallthrough to below
			}
		// flex, flex-direction, scroll-snap-type, writing-mode
		case 6828: case 4268: case 2903:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + value + value
		// order
		case 6165:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + 'flex-' + value + value
		// align-items
		case 5187:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /(\w+).+(:[^]+)/, _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + 'box-$1$2' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + 'flex-$1$2') + value
		// align-self
		case 5443:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + 'flex-item-' + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /flex-|-self/g, '') + (!(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.match)(value, /flex-|baseline/) ? _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + 'grid-row-' + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /flex-|-self/g, '') : '') + value
		// align-content
		case 4675:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + 'flex-line-pack' + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /align-content|flex-|-self/g, '') + value
		// flex-shrink
		case 5548:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, 'shrink', 'negative') + value
		// flex-basis
		case 5292:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, 'basis', 'preferred-size') + value
		// flex-grow
		case 6060:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + 'box-' + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, '-grow', '') + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, 'grow', 'positive') + value
		// transition
		case 4554:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /([^-])(transform)/g, '$1' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + '$2') + value
		// cursor
		case 6187:
			return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /(zoom-|grab)/, _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + '$1'), /(image-set)/, _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + '$1'), value, '') + value
		// background, background-image
		case 5495: case 3959:
			return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /(image-set\([^]*)/, _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + '$1' + '$`$1')
		// justify-content
		case 4968:
			return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /(.+:)(flex-)?(.*)/, _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + 'box-pack:$3' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + 'flex-pack:$3'), /s.+-b[^;]+/, 'justify') + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + value + value
		// justify-self
		case 4200:
			if (!(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.match)(value, /flex-|baseline/)) return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + 'grid-column-align' + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.substr)(value, length) + value
			break
		// grid-template-(columns|rows)
		case 2592: case 3360:
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, 'template-', '') + value
		// grid-(row|column)-start
		case 4384: case 3616:
			if (children && children.some(function (element, index) { return length = index, (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.match)(element.props, /grid-\w+-end/) })) {
				return ~(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.indexof)(value + (children = children[length].value), 'span', 0) ? value : (_Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, '-start', '') + value + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + 'grid-row-span:' + (~(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.indexof)(children, 'span', 0) ? (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.match)(children, /\d+/) : +(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.match)(children, /\d+/) - +(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.match)(value, /\d+/)) + ';')
			}
			return _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, '-start', '') + value
		// grid-(row|column)-end
		case 4896: case 4128:
			return (children && children.some(function (element) { return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.match)(element.props, /grid-\w+-start/) })) ? value : _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, '-end', '-span'), 'span ', '') + value
		// (margin|padding)-inline-(start|end)
		case 4095: case 3583: case 4068: case 2532:
			return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /(.+)-inline(.+)/, _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + '$1$2') + value
		// (min|max)?(width|height|inline-size|block-size)
		case 8116: case 7059: case 5753: case 5535:
		case 5445: case 5701: case 4933: case 4677:
		case 5533: case 5789: case 5021: case 4765:
			// stretch, max-content, min-content, fill-available
			if ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.strlen)(value) - 1 - length > 6)
				switch ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(value, length + 1)) {
					// (m)ax-content, (m)in-content
					case 109:
						// -
						if ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(value, length + 4) !== 45)
							break
					// (f)ill-available, (f)it-content
					case 102:
						return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /(.+:)(.+)-([^]+)/, '$1' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + '$2-$3' + '$1' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MOZ + ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(value, length + 3) == 108 ? '$3' : '$2-$3')) + value
					// (s)tretch
					case 115:
						return ~(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.indexof)(value, 'stretch', 0) ? prefix((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, 'stretch', 'fill-available'), length, children) + value : value
				}
			break
		// grid-(column|row)
		case 5152: case 5920:
			return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /(.+?):(\d+)(\s*\/\s*(span)?\s*(\d+))?(.*)/, function (_, a, b, c, d, e, f) { return (_Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + a + ':' + b + f) + (c ? (_Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + a + '-span:' + (d ? e : +e - +b)) + f : '') + value })
		// position: sticky
		case 4949:
			// stick(y)?
			if ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(value, length + 6) === 121)
				return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, ':', ':' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT) + value
			break
		// display: (flex|inline-flex|grid|inline-grid)
		case 6444:
			switch ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(value, (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(value, 14) === 45 ? 18 : 11)) {
				// (inline-)?fle(x)
				case 120:
					return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, /(.+:)([^;\s!]+)(;|(\s+)?!.+)?/, '$1' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + ((0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.charat)(value, 14) === 45 ? 'inline-' : '') + 'box$3' + '$1' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.WEBKIT + '$2$3' + '$1' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS + '$2box$3') + value
				// (inline-)?gri(d)
				case 100:
					return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, ':', ':' + _Enum_js__WEBPACK_IMPORTED_MODULE_0__.MS) + value
			}
			break
		// scroll-margin, scroll-margin-(top|right|bottom|left)
		case 5719: case 2647: case 2135: case 3927: case 2391:
			return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.replace)(value, 'scroll-', 'scroll-snap-') + value
	}

	return value
}


/***/ }),

/***/ "./node_modules/styled-components/node_modules/stylis/src/Serializer.js":
/*!******************************************************************************!*\
  !*** ./node_modules/styled-components/node_modules/stylis/src/Serializer.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   serialize: () => (/* binding */ serialize),
/* harmony export */   stringify: () => (/* binding */ stringify)
/* harmony export */ });
/* harmony import */ var _Enum_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Enum.js */ "./node_modules/styled-components/node_modules/stylis/src/Enum.js");
/* harmony import */ var _Utility_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Utility.js */ "./node_modules/styled-components/node_modules/stylis/src/Utility.js");



/**
 * @param {object[]} children
 * @param {function} callback
 * @return {string}
 */
function serialize (children, callback) {
	var output = ''

	for (var i = 0; i < children.length; i++)
		output += callback(children[i], i, children, callback) || ''

	return output
}

/**
 * @param {object} element
 * @param {number} index
 * @param {object[]} children
 * @param {function} callback
 * @return {string}
 */
function stringify (element, index, children, callback) {
	switch (element.type) {
		case _Enum_js__WEBPACK_IMPORTED_MODULE_0__.LAYER: if (element.children.length) break
		case _Enum_js__WEBPACK_IMPORTED_MODULE_0__.IMPORT: case _Enum_js__WEBPACK_IMPORTED_MODULE_0__.DECLARATION: return element.return = element.return || element.value
		case _Enum_js__WEBPACK_IMPORTED_MODULE_0__.COMMENT: return ''
		case _Enum_js__WEBPACK_IMPORTED_MODULE_0__.KEYFRAMES: return element.return = element.value + '{' + serialize(element.children, callback) + '}'
		case _Enum_js__WEBPACK_IMPORTED_MODULE_0__.RULESET: if (!(0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.strlen)(element.value = element.props.join(','))) return ''
	}

	return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_1__.strlen)(children = serialize(element.children, callback)) ? element.return = element.value + '{' + children + '}' : ''
}


/***/ }),

/***/ "./node_modules/styled-components/node_modules/stylis/src/Tokenizer.js":
/*!*****************************************************************************!*\
  !*** ./node_modules/styled-components/node_modules/stylis/src/Tokenizer.js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   alloc: () => (/* binding */ alloc),
/* harmony export */   caret: () => (/* binding */ caret),
/* harmony export */   char: () => (/* binding */ char),
/* harmony export */   character: () => (/* binding */ character),
/* harmony export */   characters: () => (/* binding */ characters),
/* harmony export */   column: () => (/* binding */ column),
/* harmony export */   commenter: () => (/* binding */ commenter),
/* harmony export */   copy: () => (/* binding */ copy),
/* harmony export */   dealloc: () => (/* binding */ dealloc),
/* harmony export */   delimit: () => (/* binding */ delimit),
/* harmony export */   delimiter: () => (/* binding */ delimiter),
/* harmony export */   escaping: () => (/* binding */ escaping),
/* harmony export */   identifier: () => (/* binding */ identifier),
/* harmony export */   length: () => (/* binding */ length),
/* harmony export */   lift: () => (/* binding */ lift),
/* harmony export */   line: () => (/* binding */ line),
/* harmony export */   next: () => (/* binding */ next),
/* harmony export */   node: () => (/* binding */ node),
/* harmony export */   peek: () => (/* binding */ peek),
/* harmony export */   position: () => (/* binding */ position),
/* harmony export */   prev: () => (/* binding */ prev),
/* harmony export */   slice: () => (/* binding */ slice),
/* harmony export */   token: () => (/* binding */ token),
/* harmony export */   tokenize: () => (/* binding */ tokenize),
/* harmony export */   tokenizer: () => (/* binding */ tokenizer),
/* harmony export */   whitespace: () => (/* binding */ whitespace)
/* harmony export */ });
/* harmony import */ var _Utility_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Utility.js */ "./node_modules/styled-components/node_modules/stylis/src/Utility.js");


var line = 1
var column = 1
var length = 0
var position = 0
var character = 0
var characters = ''

/**
 * @param {string} value
 * @param {object | null} root
 * @param {object | null} parent
 * @param {string} type
 * @param {string[] | string} props
 * @param {object[] | string} children
 * @param {object[]} siblings
 * @param {number} length
 */
function node (value, root, parent, type, props, children, length, siblings) {
	return {value: value, root: root, parent: parent, type: type, props: props, children: children, line: line, column: column, length: length, return: '', siblings: siblings}
}

/**
 * @param {object} root
 * @param {object} props
 * @return {object}
 */
function copy (root, props) {
	return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.assign)(node('', null, null, '', null, null, 0, root.siblings), root, {length: -root.length}, props)
}

/**
 * @param {object} root
 */
function lift (root) {
	while (root.root)
		root = copy(root.root, {children: [root]})

	;(0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.append)(root, root.siblings)
}

/**
 * @return {number}
 */
function char () {
	return character
}

/**
 * @return {number}
 */
function prev () {
	character = position > 0 ? (0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.charat)(characters, --position) : 0

	if (column--, character === 10)
		column = 1, line--

	return character
}

/**
 * @return {number}
 */
function next () {
	character = position < length ? (0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.charat)(characters, position++) : 0

	if (column++, character === 10)
		column = 1, line++

	return character
}

/**
 * @return {number}
 */
function peek () {
	return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.charat)(characters, position)
}

/**
 * @return {number}
 */
function caret () {
	return position
}

/**
 * @param {number} begin
 * @param {number} end
 * @return {string}
 */
function slice (begin, end) {
	return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.substr)(characters, begin, end)
}

/**
 * @param {number} type
 * @return {number}
 */
function token (type) {
	switch (type) {
		// \0 \t \n \r \s whitespace token
		case 0: case 9: case 10: case 13: case 32:
			return 5
		// ! + , / > @ ~ isolate token
		case 33: case 43: case 44: case 47: case 62: case 64: case 126:
		// ; { } breakpoint token
		case 59: case 123: case 125:
			return 4
		// : accompanied token
		case 58:
			return 3
		// " ' ( [ opening delimit token
		case 34: case 39: case 40: case 91:
			return 2
		// ) ] closing delimit token
		case 41: case 93:
			return 1
	}

	return 0
}

/**
 * @param {string} value
 * @return {any[]}
 */
function alloc (value) {
	return line = column = 1, length = (0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.strlen)(characters = value), position = 0, []
}

/**
 * @param {any} value
 * @return {any}
 */
function dealloc (value) {
	return characters = '', value
}

/**
 * @param {number} type
 * @return {string}
 */
function delimit (type) {
	return (0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.trim)(slice(position - 1, delimiter(type === 91 ? type + 2 : type === 40 ? type + 1 : type)))
}

/**
 * @param {string} value
 * @return {string[]}
 */
function tokenize (value) {
	return dealloc(tokenizer(alloc(value)))
}

/**
 * @param {number} type
 * @return {string}
 */
function whitespace (type) {
	while (character = peek())
		if (character < 33)
			next()
		else
			break

	return token(type) > 2 || token(character) > 3 ? '' : ' '
}

/**
 * @param {string[]} children
 * @return {string[]}
 */
function tokenizer (children) {
	while (next())
		switch (token(character)) {
			case 0: (0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.append)(identifier(position - 1), children)
				break
			case 2: ;(0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.append)(delimit(character), children)
				break
			default: ;(0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.append)((0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.from)(character), children)
		}

	return children
}

/**
 * @param {number} index
 * @param {number} count
 * @return {string}
 */
function escaping (index, count) {
	while (--count && next())
		// not 0-9 A-F a-f
		if (character < 48 || character > 102 || (character > 57 && character < 65) || (character > 70 && character < 97))
			break

	return slice(index, caret() + (count < 6 && peek() == 32 && next() == 32))
}

/**
 * @param {number} type
 * @return {number}
 */
function delimiter (type) {
	while (next())
		switch (character) {
			// ] ) " '
			case type:
				return position
			// " '
			case 34: case 39:
				if (type !== 34 && type !== 39)
					delimiter(character)
				break
			// (
			case 40:
				if (type === 41)
					delimiter(type)
				break
			// \
			case 92:
				next()
				break
		}

	return position
}

/**
 * @param {number} type
 * @param {number} index
 * @return {number}
 */
function commenter (type, index) {
	while (next())
		// //
		if (type + character === 47 + 10)
			break
		// /*
		else if (type + character === 42 + 42 && peek() === 47)
			break

	return '/*' + slice(index, position - 1) + '*' + (0,_Utility_js__WEBPACK_IMPORTED_MODULE_0__.from)(type === 47 ? type : next())
}

/**
 * @param {number} index
 * @return {string}
 */
function identifier (index) {
	while (!token(peek()))
		next()

	return slice(index, position)
}


/***/ }),

/***/ "./node_modules/styled-components/node_modules/stylis/src/Utility.js":
/*!***************************************************************************!*\
  !*** ./node_modules/styled-components/node_modules/stylis/src/Utility.js ***!
  \***************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   abs: () => (/* binding */ abs),
/* harmony export */   append: () => (/* binding */ append),
/* harmony export */   assign: () => (/* binding */ assign),
/* harmony export */   charat: () => (/* binding */ charat),
/* harmony export */   combine: () => (/* binding */ combine),
/* harmony export */   filter: () => (/* binding */ filter),
/* harmony export */   from: () => (/* binding */ from),
/* harmony export */   hash: () => (/* binding */ hash),
/* harmony export */   indexof: () => (/* binding */ indexof),
/* harmony export */   match: () => (/* binding */ match),
/* harmony export */   replace: () => (/* binding */ replace),
/* harmony export */   sizeof: () => (/* binding */ sizeof),
/* harmony export */   strlen: () => (/* binding */ strlen),
/* harmony export */   substr: () => (/* binding */ substr),
/* harmony export */   trim: () => (/* binding */ trim)
/* harmony export */ });
/**
 * @param {number}
 * @return {number}
 */
var abs = Math.abs

/**
 * @param {number}
 * @return {string}
 */
var from = String.fromCharCode

/**
 * @param {object}
 * @return {object}
 */
var assign = Object.assign

/**
 * @param {string} value
 * @param {number} length
 * @return {number}
 */
function hash (value, length) {
	return charat(value, 0) ^ 45 ? (((((((length << 2) ^ charat(value, 0)) << 2) ^ charat(value, 1)) << 2) ^ charat(value, 2)) << 2) ^ charat(value, 3) : 0
}

/**
 * @param {string} value
 * @return {string}
 */
function trim (value) {
	return value.trim()
}

/**
 * @param {string} value
 * @param {RegExp} pattern
 * @return {string?}
 */
function match (value, pattern) {
	return (value = pattern.exec(value)) ? value[0] : value
}

/**
 * @param {string} value
 * @param {(string|RegExp)} pattern
 * @param {string} replacement
 * @return {string}
 */
function replace (value, pattern, replacement) {
	return value.replace(pattern, replacement)
}

/**
 * @param {string} value
 * @param {string} search
 * @param {number} position
 * @return {number}
 */
function indexof (value, search, position) {
	return value.indexOf(search, position)
}

/**
 * @param {string} value
 * @param {number} index
 * @return {number}
 */
function charat (value, index) {
	return value.charCodeAt(index) | 0
}

/**
 * @param {string} value
 * @param {number} begin
 * @param {number} end
 * @return {string}
 */
function substr (value, begin, end) {
	return value.slice(begin, end)
}

/**
 * @param {string} value
 * @return {number}
 */
function strlen (value) {
	return value.length
}

/**
 * @param {any[]} value
 * @return {number}
 */
function sizeof (value) {
	return value.length
}

/**
 * @param {any} value
 * @param {any[]} array
 * @return {any}
 */
function append (value, array) {
	return array.push(value), value
}

/**
 * @param {string[]} array
 * @param {function} callback
 * @return {string}
 */
function combine (array, callback) {
	return array.map(callback).join('')
}

/**
 * @param {string[]} array
 * @param {RegExp} pattern
 * @return {string[]}
 */
function filter (array, pattern) {
	return array.filter(function (value) { return !match(value, pattern) })
}


/***/ }),

/***/ "./node_modules/styled-components/node_modules/tslib/tslib.es6.mjs":
/*!*************************************************************************!*\
  !*** ./node_modules/styled-components/node_modules/tslib/tslib.es6.mjs ***!
  \*************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   __addDisposableResource: () => (/* binding */ __addDisposableResource),
/* harmony export */   __assign: () => (/* binding */ __assign),
/* harmony export */   __asyncDelegator: () => (/* binding */ __asyncDelegator),
/* harmony export */   __asyncGenerator: () => (/* binding */ __asyncGenerator),
/* harmony export */   __asyncValues: () => (/* binding */ __asyncValues),
/* harmony export */   __await: () => (/* binding */ __await),
/* harmony export */   __awaiter: () => (/* binding */ __awaiter),
/* harmony export */   __classPrivateFieldGet: () => (/* binding */ __classPrivateFieldGet),
/* harmony export */   __classPrivateFieldIn: () => (/* binding */ __classPrivateFieldIn),
/* harmony export */   __classPrivateFieldSet: () => (/* binding */ __classPrivateFieldSet),
/* harmony export */   __createBinding: () => (/* binding */ __createBinding),
/* harmony export */   __decorate: () => (/* binding */ __decorate),
/* harmony export */   __disposeResources: () => (/* binding */ __disposeResources),
/* harmony export */   __esDecorate: () => (/* binding */ __esDecorate),
/* harmony export */   __exportStar: () => (/* binding */ __exportStar),
/* harmony export */   __extends: () => (/* binding */ __extends),
/* harmony export */   __generator: () => (/* binding */ __generator),
/* harmony export */   __importDefault: () => (/* binding */ __importDefault),
/* harmony export */   __importStar: () => (/* binding */ __importStar),
/* harmony export */   __makeTemplateObject: () => (/* binding */ __makeTemplateObject),
/* harmony export */   __metadata: () => (/* binding */ __metadata),
/* harmony export */   __param: () => (/* binding */ __param),
/* harmony export */   __propKey: () => (/* binding */ __propKey),
/* harmony export */   __read: () => (/* binding */ __read),
/* harmony export */   __rest: () => (/* binding */ __rest),
/* harmony export */   __runInitializers: () => (/* binding */ __runInitializers),
/* harmony export */   __setFunctionName: () => (/* binding */ __setFunctionName),
/* harmony export */   __spread: () => (/* binding */ __spread),
/* harmony export */   __spreadArray: () => (/* binding */ __spreadArray),
/* harmony export */   __spreadArrays: () => (/* binding */ __spreadArrays),
/* harmony export */   __values: () => (/* binding */ __values),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */
/* global Reflect, Promise, SuppressedError, Symbol */

var extendStatics = function(d, b) {
  extendStatics = Object.setPrototypeOf ||
      ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
      function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
  return extendStatics(d, b);
};

function __extends(d, b) {
  if (typeof b !== "function" && b !== null)
      throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
  extendStatics(d, b);
  function __() { this.constructor = d; }
  d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
}

var __assign = function() {
  __assign = Object.assign || function __assign(t) {
      for (var s, i = 1, n = arguments.length; i < n; i++) {
          s = arguments[i];
          for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
      }
      return t;
  }
  return __assign.apply(this, arguments);
}

function __rest(s, e) {
  var t = {};
  for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
      t[p] = s[p];
  if (s != null && typeof Object.getOwnPropertySymbols === "function")
      for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
          if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
              t[p[i]] = s[p[i]];
      }
  return t;
}

function __decorate(decorators, target, key, desc) {
  var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
  if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
  else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
  return c > 3 && r && Object.defineProperty(target, key, r), r;
}

function __param(paramIndex, decorator) {
  return function (target, key) { decorator(target, key, paramIndex); }
}

function __esDecorate(ctor, descriptorIn, decorators, contextIn, initializers, extraInitializers) {
  function accept(f) { if (f !== void 0 && typeof f !== "function") throw new TypeError("Function expected"); return f; }
  var kind = contextIn.kind, key = kind === "getter" ? "get" : kind === "setter" ? "set" : "value";
  var target = !descriptorIn && ctor ? contextIn["static"] ? ctor : ctor.prototype : null;
  var descriptor = descriptorIn || (target ? Object.getOwnPropertyDescriptor(target, contextIn.name) : {});
  var _, done = false;
  for (var i = decorators.length - 1; i >= 0; i--) {
      var context = {};
      for (var p in contextIn) context[p] = p === "access" ? {} : contextIn[p];
      for (var p in contextIn.access) context.access[p] = contextIn.access[p];
      context.addInitializer = function (f) { if (done) throw new TypeError("Cannot add initializers after decoration has completed"); extraInitializers.push(accept(f || null)); };
      var result = (0, decorators[i])(kind === "accessor" ? { get: descriptor.get, set: descriptor.set } : descriptor[key], context);
      if (kind === "accessor") {
          if (result === void 0) continue;
          if (result === null || typeof result !== "object") throw new TypeError("Object expected");
          if (_ = accept(result.get)) descriptor.get = _;
          if (_ = accept(result.set)) descriptor.set = _;
          if (_ = accept(result.init)) initializers.unshift(_);
      }
      else if (_ = accept(result)) {
          if (kind === "field") initializers.unshift(_);
          else descriptor[key] = _;
      }
  }
  if (target) Object.defineProperty(target, contextIn.name, descriptor);
  done = true;
};

function __runInitializers(thisArg, initializers, value) {
  var useValue = arguments.length > 2;
  for (var i = 0; i < initializers.length; i++) {
      value = useValue ? initializers[i].call(thisArg, value) : initializers[i].call(thisArg);
  }
  return useValue ? value : void 0;
};

function __propKey(x) {
  return typeof x === "symbol" ? x : "".concat(x);
};

function __setFunctionName(f, name, prefix) {
  if (typeof name === "symbol") name = name.description ? "[".concat(name.description, "]") : "";
  return Object.defineProperty(f, "name", { configurable: true, value: prefix ? "".concat(prefix, " ", name) : name });
};

function __metadata(metadataKey, metadataValue) {
  if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(metadataKey, metadataValue);
}

function __awaiter(thisArg, _arguments, P, generator) {
  function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
  return new (P || (P = Promise))(function (resolve, reject) {
      function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
      function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
      function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
      step((generator = generator.apply(thisArg, _arguments || [])).next());
  });
}

function __generator(thisArg, body) {
  var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
  return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
  function verb(n) { return function (v) { return step([n, v]); }; }
  function step(op) {
      if (f) throw new TypeError("Generator is already executing.");
      while (g && (g = 0, op[0] && (_ = 0)), _) try {
          if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
          if (y = 0, t) op = [op[0] & 2, t.value];
          switch (op[0]) {
              case 0: case 1: t = op; break;
              case 4: _.label++; return { value: op[1], done: false };
              case 5: _.label++; y = op[1]; op = [0]; continue;
              case 7: op = _.ops.pop(); _.trys.pop(); continue;
              default:
                  if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                  if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                  if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                  if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                  if (t[2]) _.ops.pop();
                  _.trys.pop(); continue;
          }
          op = body.call(thisArg, _);
      } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
      if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
  }
}

var __createBinding = Object.create ? (function(o, m, k, k2) {
  if (k2 === undefined) k2 = k;
  var desc = Object.getOwnPropertyDescriptor(m, k);
  if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
  }
  Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
  if (k2 === undefined) k2 = k;
  o[k2] = m[k];
});

function __exportStar(m, o) {
  for (var p in m) if (p !== "default" && !Object.prototype.hasOwnProperty.call(o, p)) __createBinding(o, m, p);
}

function __values(o) {
  var s = typeof Symbol === "function" && Symbol.iterator, m = s && o[s], i = 0;
  if (m) return m.call(o);
  if (o && typeof o.length === "number") return {
      next: function () {
          if (o && i >= o.length) o = void 0;
          return { value: o && o[i++], done: !o };
      }
  };
  throw new TypeError(s ? "Object is not iterable." : "Symbol.iterator is not defined.");
}

function __read(o, n) {
  var m = typeof Symbol === "function" && o[Symbol.iterator];
  if (!m) return o;
  var i = m.call(o), r, ar = [], e;
  try {
      while ((n === void 0 || n-- > 0) && !(r = i.next()).done) ar.push(r.value);
  }
  catch (error) { e = { error: error }; }
  finally {
      try {
          if (r && !r.done && (m = i["return"])) m.call(i);
      }
      finally { if (e) throw e.error; }
  }
  return ar;
}

/** @deprecated */
function __spread() {
  for (var ar = [], i = 0; i < arguments.length; i++)
      ar = ar.concat(__read(arguments[i]));
  return ar;
}

/** @deprecated */
function __spreadArrays() {
  for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
  for (var r = Array(s), k = 0, i = 0; i < il; i++)
      for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
          r[k] = a[j];
  return r;
}

function __spreadArray(to, from, pack) {
  if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
      if (ar || !(i in from)) {
          if (!ar) ar = Array.prototype.slice.call(from, 0, i);
          ar[i] = from[i];
      }
  }
  return to.concat(ar || Array.prototype.slice.call(from));
}

function __await(v) {
  return this instanceof __await ? (this.v = v, this) : new __await(v);
}

function __asyncGenerator(thisArg, _arguments, generator) {
  if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
  var g = generator.apply(thisArg, _arguments || []), i, q = [];
  return i = {}, verb("next"), verb("throw"), verb("return"), i[Symbol.asyncIterator] = function () { return this; }, i;
  function verb(n) { if (g[n]) i[n] = function (v) { return new Promise(function (a, b) { q.push([n, v, a, b]) > 1 || resume(n, v); }); }; }
  function resume(n, v) { try { step(g[n](v)); } catch (e) { settle(q[0][3], e); } }
  function step(r) { r.value instanceof __await ? Promise.resolve(r.value.v).then(fulfill, reject) : settle(q[0][2], r); }
  function fulfill(value) { resume("next", value); }
  function reject(value) { resume("throw", value); }
  function settle(f, v) { if (f(v), q.shift(), q.length) resume(q[0][0], q[0][1]); }
}

function __asyncDelegator(o) {
  var i, p;
  return i = {}, verb("next"), verb("throw", function (e) { throw e; }), verb("return"), i[Symbol.iterator] = function () { return this; }, i;
  function verb(n, f) { i[n] = o[n] ? function (v) { return (p = !p) ? { value: __await(o[n](v)), done: false } : f ? f(v) : v; } : f; }
}

function __asyncValues(o) {
  if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
  var m = o[Symbol.asyncIterator], i;
  return m ? m.call(o) : (o = typeof __values === "function" ? __values(o) : o[Symbol.iterator](), i = {}, verb("next"), verb("throw"), verb("return"), i[Symbol.asyncIterator] = function () { return this; }, i);
  function verb(n) { i[n] = o[n] && function (v) { return new Promise(function (resolve, reject) { v = o[n](v), settle(resolve, reject, v.done, v.value); }); }; }
  function settle(resolve, reject, d, v) { Promise.resolve(v).then(function(v) { resolve({ value: v, done: d }); }, reject); }
}

function __makeTemplateObject(cooked, raw) {
  if (Object.defineProperty) { Object.defineProperty(cooked, "raw", { value: raw }); } else { cooked.raw = raw; }
  return cooked;
};

var __setModuleDefault = Object.create ? (function(o, v) {
  Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
  o["default"] = v;
};

function __importStar(mod) {
  if (mod && mod.__esModule) return mod;
  var result = {};
  if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
  __setModuleDefault(result, mod);
  return result;
}

function __importDefault(mod) {
  return (mod && mod.__esModule) ? mod : { default: mod };
}

function __classPrivateFieldGet(receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
}

function __classPrivateFieldSet(receiver, state, value, kind, f) {
  if (kind === "m") throw new TypeError("Private method is not writable");
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a setter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot write private member to an object whose class did not declare it");
  return (kind === "a" ? f.call(receiver, value) : f ? f.value = value : state.set(receiver, value)), value;
}

function __classPrivateFieldIn(state, receiver) {
  if (receiver === null || (typeof receiver !== "object" && typeof receiver !== "function")) throw new TypeError("Cannot use 'in' operator on non-object");
  return typeof state === "function" ? receiver === state : state.has(receiver);
}

function __addDisposableResource(env, value, async) {
  if (value !== null && value !== void 0) {
    if (typeof value !== "object" && typeof value !== "function") throw new TypeError("Object expected.");
    var dispose;
    if (async) {
        if (!Symbol.asyncDispose) throw new TypeError("Symbol.asyncDispose is not defined.");
        dispose = value[Symbol.asyncDispose];
    }
    if (dispose === void 0) {
        if (!Symbol.dispose) throw new TypeError("Symbol.dispose is not defined.");
        dispose = value[Symbol.dispose];
    }
    if (typeof dispose !== "function") throw new TypeError("Object not disposable.");
    env.stack.push({ value: value, dispose: dispose, async: async });
  }
  else if (async) {
    env.stack.push({ async: true });
  }
  return value;
}

var _SuppressedError = typeof SuppressedError === "function" ? SuppressedError : function (error, suppressed, message) {
  var e = new Error(message);
  return e.name = "SuppressedError", e.error = error, e.suppressed = suppressed, e;
};

function __disposeResources(env) {
  function fail(e) {
    env.error = env.hasError ? new _SuppressedError(e, env.error, "An error was suppressed during disposal.") : e;
    env.hasError = true;
  }
  function next() {
    while (env.stack.length) {
      var rec = env.stack.pop();
      try {
        var result = rec.dispose && rec.dispose.call(rec.value);
        if (rec.async) return Promise.resolve(result).then(next, function(e) { fail(e); return next(); });
      }
      catch (e) {
          fail(e);
      }
    }
    if (env.hasError) throw env.error;
  }
  return next();
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __extends,
  __assign,
  __rest,
  __decorate,
  __param,
  __metadata,
  __awaiter,
  __generator,
  __createBinding,
  __exportStar,
  __values,
  __read,
  __spread,
  __spreadArrays,
  __spreadArray,
  __await,
  __asyncGenerator,
  __asyncDelegator,
  __asyncValues,
  __makeTemplateObject,
  __importStar,
  __importDefault,
  __classPrivateFieldGet,
  __classPrivateFieldSet,
  __classPrivateFieldIn,
  __addDisposableResource,
  __disposeResources,
});


/***/ }),

/***/ "./src/hooks/useInterval.js":
/*!**********************************!*\
  !*** ./src/hooks/useInterval.js ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ useInterval)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

function useInterval(callback, delay) {
  const savedCallback = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();

  // Remember the latest callback.
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    savedCallback.current = callback;
  }, [callback]);

  // Set up the interval.
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    function tick() {
      savedCallback.current();
    }
    if (delay !== null) {
      let id = setInterval(tick, delay);
      return () => clearInterval(id);
    }
  }, [delay]);
}

/***/ }),

/***/ "./src/settings/Settings.js":
/*!**********************************!*\
  !*** ./src/settings/Settings.js ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _components_SettingsPage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/SettingsPage */ "./src/settings/components/SettingsPage.jsx");
/* harmony import */ var _settings_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./settings.scss */ "./src/settings/settings.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);




function Settings() {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_0__["default"], {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_components_SettingsPage__WEBPACK_IMPORTED_MODULE_1__["default"], {})
    })
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Settings);

/***/ }),

/***/ "./src/settings/components/ActivityLog.jsx":
/*!*************************************************!*\
  !*** ./src/settings/components/ActivityLog.jsx ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var react_terminal_ui__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react-terminal-ui */ "./node_modules/react-terminal-ui/build/index.es.js");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _hooks_useInterval__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../hooks/useInterval */ "./src/hooks/useInterval.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);






const {
  __
} = wp.i18n;
function ActivityLog() {
  const {
    isRunning,
    isResumed,
    isPaused,
    blogId
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_1__.SettingsContext);
  const [terminalLineData, setTerminalLineData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_terminal_ui__WEBPACK_IMPORTED_MODULE_2__.TerminalOutput, {
    children: "Waiting for new export.."
  }, "waiting")]);
  function refreshActivityLog() {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: '/simplystatic/v1/activity-log?blog_id=' + blogId + '&is_network_admin=' + options.is_network,
      method: 'GET'
    }).then(resp => {
      var json = JSON.parse(resp);
      var terminal = [];
      for (var message in json.data) {
        var date = json.data[message].datetime;
        var text = json.data[message].message;
        var error = message.includes('pause') || message.includes('cancel');
        var success = message.includes('resume');
        terminal.push(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_terminal_ui__WEBPACK_IMPORTED_MODULE_2__.TerminalOutput, {
          children: ["[", date, "] ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("span", {
            className: `${error ? 'is-error' : ''} ${success ? 'is-success' : ''}`,
            dangerouslySetInnerHTML: {
              __html: text
            }
          })]
        }, message));
      }
      setTerminalLineData(terminal);
    });
  }
  (0,_hooks_useInterval__WEBPACK_IMPORTED_MODULE_4__["default"])(() => {
    refreshActivityLog();
  }, isRunning ? 2500 : null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (isRunning && !isResumed) {
      setTerminalLineData([/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_terminal_ui__WEBPACK_IMPORTED_MODULE_2__.TerminalOutput, {
        children: "Waiting for new export.."
      }, "waiting")]);
    }
    if (isRunning && isResumed) {
      setTerminalLineData([/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_terminal_ui__WEBPACK_IMPORTED_MODULE_2__.TerminalOutput, {
        children: "Resuming the export.."
      }, "resuming")]);
    }
    refreshActivityLog();
  }, [isRunning]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_terminal_ui__WEBPACK_IMPORTED_MODULE_2__["default"], {
    name: __('Activity Log', 'simply-static'),
    height: "250px",
    colorMode: react_terminal_ui__WEBPACK_IMPORTED_MODULE_2__.ColorMode.Dark,
    children: terminalLineData
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ActivityLog);

/***/ }),

/***/ "./src/settings/components/EnvironmentSidebar.jsx":
/*!********************************************************!*\
  !*** ./src/settings/components/EnvironmentSidebar.jsx ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ EnvironmentSidebar)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Environments_EnvironmentForm__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Environments/EnvironmentForm */ "./src/settings/components/Environments/EnvironmentForm.jsx");
/* harmony import */ var _Environments_EnvironmentsDropdown__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Environments/EnvironmentsDropdown */ "./src/settings/components/Environments/EnvironmentsDropdown.jsx");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);






const {
  __
} = wp.i18n;
function EnvironmentSidebar({
  getSettings,
  isRunning
}) {
  const [selectedEnvironment, setSelectedEnvironment] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [selectableEnvironments, setSelectableEnvironments] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [showingEnvironmentForm, setShowingEnvironmentForm] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [changingEnvironment, setChangingEnvironment] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4___default()({
      path: '/simplystatic/v1/environment',
      method: 'GET'
    }).then(resp => {
      let environments = Object.keys(resp.environments).map(function (version) {
        return {
          label: resp.environments[version],
          value: version
        };
      });
      setSelectableEnvironments(environments);
      setSelectedEnvironment(resp.current_environment);
    });
  }, []);
  const deleteCurrentVersion = () => {
    setChangingEnvironment(true);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4___default()({
      path: '/simplystatic/v1/environment',
      method: 'DELETE',
      data: {
        version: selectedEnvironment
      }
    }).then(resp => {
      getSettings();
      let environments = Object.keys(resp.environments).map(function (version) {
        return {
          label: resp.environments[version],
          value: version
        };
      });
      setSelectableEnvironments(environments);
      setSelectedEnvironment(resp.current_environment);
    }).catch(resp => alert(resp.message)).finally(() => {
      setChangingEnvironment(false);
    });
  };
  const updateCurrentVersion = version => {
    setChangingEnvironment(true);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4___default()({
      path: '/simplystatic/v1/environment',
      method: 'PUT',
      data: {
        version: version
      }
    }).then(() => {
      getSettings();
      setSelectedEnvironment(version);
    }).catch(resp => alert(resp.message)).finally(() => {
      setChangingEnvironment(false);
    });
  };
  const currentVersion = () => {
    if (changingEnvironment) {
      return __('Changing ...', 'simply-static');
    }
    return selectableEnvironments.filter(item => {
      return item.value === selectedEnvironment;
    }).pop().label;
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("div", {
    className: "environment-container",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("h4", {
      className: "settings-headline",
      children: [" ", __('Environment', 'simply-static')]
    }), !showingEnvironmentForm && selectedEnvironment && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("p", {
      children: ["Current: ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("strong", {
        children: currentVersion()
      })]
    }), !showingEnvironmentForm && selectableEnvironments.length > 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_Environments_EnvironmentsDropdown__WEBPACK_IMPORTED_MODULE_3__["default"], {
      onChange: updateCurrentVersion,
      environments: selectableEnvironments,
      onDelete: deleteCurrentVersion,
      current: selectedEnvironment,
      disabled: isRunning || changingEnvironment
    }), !showingEnvironmentForm && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
      disabled: isRunning || changingEnvironment,
      variant: "primary",
      size: "large",
      onClick: () => setShowingEnvironmentForm(true),
      children: "Create an Environment"
    }), showingEnvironmentForm && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_Environments_EnvironmentForm__WEBPACK_IMPORTED_MODULE_2__["default"], {
      onClose: () => setShowingEnvironmentForm(false),
      setSelectedEnvironment: setSelectedEnvironment,
      setSelectableEnvironments: setSelectableEnvironments
    })]
  });
}

/***/ }),

/***/ "./src/settings/components/Environments/EnvironmentForm.jsx":
/*!******************************************************************!*\
  !*** ./src/settings/components/Environments/EnvironmentForm.jsx ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ EnvironmentForm)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);




const {
  __
} = wp.i18n;
function EnvironmentForm({
  onClose,
  setSelectableEnvironments,
  setSelectedEnvironment
}) {
  const [name, setName] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [creating, setCreating] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const createNew = () => {
    setCreating(true);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: '/simplystatic/v1/environment',
      method: 'POST',
      data: {
        title: name
      }
    }).then(resp => {
      let environments = Object.keys(resp.environments).map(function (version) {
        return {
          label: resp.environments[version],
          value: version
        };
      });
      setSelectableEnvironments(environments);
      setSelectedEnvironment(resp.current_environment);
      onClose();
    }).catch(resp => {
      alert(resp.message);
    }).finally(() => setCreating(false));
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
    className: 'ss-environment-form',
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
      label: "Name",
      __next40pxDefaultSize: true,
      __nextHasNoMarginBottom: true,
      onChange: val => setName(val),
      value: name
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("p", {
      children: __('A new environment will be created with the current configuration.', 'simply-static')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexBlock, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          variant: 'primary',
          onClick: createNew,
          isBusy: creating,
          children: creating ? __('Creating...', 'simply-static') : __('Create', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexBlock, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          variant: 'secondary',
          onClick: onClose,
          children: __('Cancel', 'simply-static')
        })
      })]
    })]
  });
}

/***/ }),

/***/ "./src/settings/components/Environments/EnvironmentsDropdown.jsx":
/*!***********************************************************************!*\
  !*** ./src/settings/components/Environments/EnvironmentsDropdown.jsx ***!
  \***********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ EnvironmentDropdown)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);


const {
  __
} = wp.i18n;
function EnvironmentDropdown({
  onChange,
  current,
  environments,
  disabled,
  onDelete
}) {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
      style: {
        minWidth: "80%"
      },
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
        disabled: disabled,
        value: current,
        options: environments,
        help: __('Choose an environment or create a new one to configure settings.', 'simply-static'),
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true,
        onChange: onChange
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        className: 'environment-delete-button',
        variant: 'tertiary',
        label: __('Delete selected environment', 'simply-static'),
        size: 'small',
        icon: 'trash',
        disabled: disabled,
        onClick: onDelete
      })
    })]
  });
}

/***/ }),

/***/ "./src/settings/components/ExportLog.jsx":
/*!***********************************************!*\
  !*** ./src/settings/components/ExportLog.jsx ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _hooks_useInterval__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../hooks/useInterval */ "./src/hooks/useInterval.js");
/* harmony import */ var react_data_table_component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react-data-table-component */ "./node_modules/react-data-table-component/dist/index.cjs.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__);







function ExportLog() {
  const {
    isRunning,
    blogId,
    isPro,
    settings
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_1__.SettingsContext);
  const [exportLog, setExportLog] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [loadingExportLog, setLoadingExportLog] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [perPageExportLog, setPerPageExportLog] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(25);
  const [exportPage, setExportPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  const [filterText, setFilterText] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [allData, setAllData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [loadingAllData, setLoadingAllData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [totalPages, setTotalPages] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  const [exportType, setExportType] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('export');
  const [exportTypeId, setExportTypeId] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);

  // Determine export type based on available information
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    // If delivery method is 'zip', always use 'export' type
    if (settings && settings.delivery_method === 'zip') {
      setExportType('export');
      return;
    }

    // Use the new export-type endpoint to get the export type information
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: '/simplystatic/v1/export-type',
      method: 'GET'
    }).then(response => {
      const json = JSON.parse(response);
      if (json.status === 200 && json.data) {
        // If delivery method is 'zip', override with 'export' type
        if (settings && settings.delivery_method === 'zip') {
          setExportType('export');
        } else {
          setExportType(json.data.export_type);
          setExportTypeId(json.data.export_type_id);
        }
      }
    }).catch(error => {
      console.error('Error fetching export type:', error);
      // Fallback to using options.last_export_end
      if (settings && settings.delivery_method === 'zip') {
        setExportType('export');
      } else if (options.last_export_end) {
        setExportType('Update');
      } else {
        setExportType('export');
      }
    });
  }, [settings]);

  // Define base columns
  const urlColumnWidth = isPro() ? '40%' : '60%';
  const baseColumns = [{
    name: 'Code',
    selector: row => row.code,
    sortable: true,
    width: '12%'
  }, {
    name: 'URL',
    selector: row => {
      // Display a safe path without throwing on invalid URLs
      const raw = row && typeof row.url === 'string' ? row.url : '';
      if (!raw) {
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("span", {
          children: "-"
        });
      }
      // Default to the raw value
      let pathOnly = raw;
      // If it's a relative path, just show it as-is
      if (raw.startsWith('/')) {
        pathOnly = raw;
      } else {
        // Try to parse absolute URLs safely
        try {
          const parsed = new URL(raw);
          pathOnly = parsed.pathname + parsed.search + parsed.hash;
        } catch (e) {
          // Parsing failed (e.g., protocol-relative or malformed). Keep raw.
        }
      }
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("a", {
        target: '_blank',
        href: raw,
        children: pathOnly
      });
    },
    sortable: true,
    sortFunction: (rowA, rowB) => rowA.url.localeCompare(rowB.url),
    width: urlColumnWidth,
    wrap: true
  }];

  // Define Export-Type column (only included if Pro is activated)
  const exportTypeColumn = {
    name: 'Export-Type',
    selector: row => {
      // Display the export type and ID if it's a Build or Single export
      if (exportType === 'Build' || exportType === 'Single') {
        return `${exportType} (ID: ${exportTypeId})`;
      }
      return exportType;
    },
    sortable: true,
    width: '20%'
  };

  // Define Notes column
  const notesColumn = {
    name: 'Notes',
    wrap: true,
    selector: row => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("span", {
      dangerouslySetInnerHTML: {
        __html: row.notes
      }
    }),
    sortable: true,
    sortFunction: (rowA, rowB) => {
      // Remove HTML tags for sorting
      const notesA = rowA.notes.replace(/<[^>]*>/g, '');
      const notesB = rowB.notes.replace(/<[^>]*>/g, '');
      return notesA.localeCompare(notesB);
    },
    width: '28%'
  };

  // Combine columns based on whether Pro is activated
  const columns = isPro() ? [...baseColumns, exportTypeColumn, notesColumn] : [...baseColumns, notesColumn];
  const handlePageChange = page => {
    getExportLog(page);
  };
  const handlePerRowsChange = (newPerPage, page) => {
    setPerPageExportLog(newPerPage);
    getExportLog(page, true);
  };
  const handleSearch = async e => {
    const searchTerm = e.target.value;
    setFilterText(searchTerm);

    // If search term is not empty and we don't have all data yet, fetch all data
    // But only if we're not running a Build or Single export
    if (searchTerm && allData.length === 0 && exportType !== 'Build' && exportType !== 'Single') {
      await fetchAllData();
      setLastAllDataFetch(Date.now()); // Update the timestamp after fetching
    }
  };
  function getExportLog(page, force = false) {
    page = page !== null && page !== void 0 ? page : 1;
    if (page !== exportPage || force) {
      setLoadingExportLog(true);
    }
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: `/simplystatic/v1/export-log?page=${page}&per_page=${perPageExportLog}&blog_id=${blogId}&is_network_admin=${options.is_network}`,
      method: 'GET'
    }).then(resp => {
      var json = JSON.parse(resp);
      if (page !== exportPage || force) {
        setExportLog(json.data);
        setLoadingExportLog(false);

        // Calculate total pages
        const total = json.data.total_static_pages || 0;
        const calculatedTotalPages = Math.ceil(total / perPageExportLog);
        setTotalPages(calculatedTotalPages);
      } else {
        exportLog.total_static_pages = json.data.total_static_pages;
        setExportLog(exportLog);
      }
      setExportPage(page);
    });
  }

  // Function to fetch all data for search
  async function fetchAllData() {
    setLoadingAllData(true);
    try {
      // First, get the first page to determine total pages
      const firstPageResponse = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/simplystatic/v1/export-log?page=1&per_page=${perPageExportLog}&blog_id=${blogId}&is_network_admin=${options.is_network}`,
        method: 'GET'
      });
      const firstPageJson = JSON.parse(firstPageResponse);
      const totalItems = firstPageJson.data.total_static_pages || 0;
      let calculatedTotalPages = Math.ceil(totalItems / perPageExportLog);

      // For very large sites, limit the number of pages we fetch to avoid timeouts
      const MAX_PAGES_TO_FETCH = 20; // This will fetch up to 500 items with default perPage of 25
      if (calculatedTotalPages > MAX_PAGES_TO_FETCH) {
        console.log(`Site has ${calculatedTotalPages} pages of data, limiting to ${MAX_PAGES_TO_FETCH} pages to prevent timeouts`);
        calculatedTotalPages = MAX_PAGES_TO_FETCH;
      }

      // Instead of fetching all pages at once, fetch them in batches
      const BATCH_SIZE = 5; // Process 5 pages at a time
      let allPages = [];

      // Add the first page data we already fetched
      if (firstPageJson.data && firstPageJson.data.static_pages) {
        allPages = [...firstPageJson.data.static_pages];
      }

      // Process remaining pages in batches
      for (let batchStart = 2; batchStart <= calculatedTotalPages; batchStart += BATCH_SIZE) {
        const batchEnd = Math.min(batchStart + BATCH_SIZE - 1, calculatedTotalPages);
        console.log(`Fetching batch of pages ${batchStart} to ${batchEnd}`);

        // Create batch of promises
        const batchPromises = [];
        for (let i = batchStart; i <= batchEnd; i++) {
          batchPromises.push(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
            path: `/simplystatic/v1/export-log?page=${i}&per_page=${perPageExportLog}&blog_id=${blogId}&is_network_admin=${options.is_network}`,
            method: 'GET'
          }));
        }

        // Execute batch of promises
        const batchResponses = await Promise.all(batchPromises);

        // Process batch responses
        batchResponses.forEach(response => {
          const json = JSON.parse(response);
          if (json.data && json.data.static_pages) {
            allPages = [...allPages, ...json.data.static_pages];
          }
        });
      }

      // Update state with the fetched data
      setAllData(allPages);

      // Log for debugging
      console.log(`Fetched ${allPages.length} total items from ${calculatedTotalPages} pages (out of ${Math.ceil(totalItems / perPageExportLog)} total pages)`);
      return allPages;
    } catch (error) {
      console.error('Error fetching all data:', error);
      return [];
    } finally {
      setLoadingAllData(false);
    }
  }

  // Track the last time we fetched all data
  const [lastAllDataFetch, setLastAllDataFetch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  (0,_hooks_useInterval__WEBPACK_IMPORTED_MODULE_3__["default"])(() => {
    getExportLog();

    // If we have a search term and already have all data, refresh the all data
    // but limit how often we do this to prevent overloading the server
    const currentTime = Date.now();
    const ALL_DATA_REFRESH_INTERVAL = 30000; // 30 seconds between full refreshes

    if (filterText && allData.length > 0 && currentTime - lastAllDataFetch > ALL_DATA_REFRESH_INTERVAL) {
      console.log('Refreshing all data for search (30-second interval)');
      fetchAllData();
      setLastAllDataFetch(currentTime);
    }
  }, isRunning ? 5000 : null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    getExportLog(1, true);

    // Fetch the export type when isRunning changes
    if (isRunning) {
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/simplystatic/v1/export-type',
        method: 'GET'
      }).then(response => {
        const json = JSON.parse(response);
        if (json.status === 200 && json.data) {
          setExportType(json.data.export_type);
          setExportTypeId(json.data.export_type_id);
        }
      }).catch(error => {
        console.error('Error fetching export type:', error);
      });
    }
  }, [isRunning]);

  // Filter data based on search term
  // When running a Build or Single export, only search in the current export log data
  const dataToFilter = filterText && allData.length > 0 && exportType !== 'Build' && exportType !== 'Single' ? allData : exportLog.static_pages || [];
  const filteredData = dataToFilter.filter(item => {
    if (!filterText) return true;
    const searchTerm = filterText.toLowerCase();
    return item.code && item.code.toString().toLowerCase().includes(searchTerm) || item.url && item.url.toLowerCase().includes(searchTerm) || item.notes && item.notes.toLowerCase().includes(searchTerm);
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
    className: "log-table-container",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Flex, {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.FlexItem, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("input", {
          id: "export-search",
          className: 'ss-export-log-search',
          type: "text",
          placeholder: "Search...",
          value: filterText,
          onChange: handleSearch
        })
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(react_data_table_component__WEBPACK_IMPORTED_MODULE_4__["default"], {
      columns: columns,
      data: filterText ? filteredData : exportLog.static_pages || [],
      pagination: true,
      paginationServer: !filterText,
      paginationTotalRows: filterText ? filteredData.length : exportLog.total_static_pages,
      paginationPerPage: 25,
      paginationRowsPerPageOptions: [25, 50, 100, 200],
      progressPending: loadingExportLog || loadingAllData && filterText,
      progressComponent: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
        style: {
          padding: '24px',
          textAlign: 'center'
        },
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Spinner, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
          style: {
            marginTop: '8px'
          },
          children: loadingAllData && filterText ? 'Loading all data for search...' : 'Loading...'
        })]
      }),
      onChangeRowsPerPage: handlePerRowsChange,
      onChangePage: filterText ? undefined : handlePageChange
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ExportLog);

/***/ }),

/***/ "./src/settings/components/GenerateButtons.jsx":
/*!*****************************************************!*\
  !*** ./src/settings/components/GenerateButtons.jsx ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);


const {
  __
} = wp.i18n;
function GenerateButtons(props) {
  const {
    children,
    canGenerate,
    isPaused,
    isDelayed,
    startExport,
    cancelExport,
    pauseExport,
    resumeExport
  } = props;
  const hasPauseFeature = typeof pauseExport === 'function';
  const hasCancelFeature = typeof cancelExport === 'function';
  const hasResumeFeature = typeof resumeExport === 'function';
  const hasExportFeature = typeof startExport === 'function';
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
    className: "generate-buttons-container",
    children: [canGenerate && hasExportFeature && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
      onClick: () => {
        startExport();
      },
      disabled: !canGenerate || isDelayed,
      className: 'generate',
      children: [canGenerate && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.Fragment, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "update"
        }), __('Generate', 'simply-static')]
      }), canGenerate && isDelayed > 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.Fragment, {
        children: [" ", isDelayed, "s"]
      }), !canGenerate && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
        icon: "update spin"
      })]
    }), !canGenerate && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.Fragment, {
      children: [!isPaused && hasPauseFeature && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        label: __('Pause', 'simply-static'),
        className: "ss-generate-media-button",
        showToolTip: true,
        onClick: () => pauseExport(),
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "controls-pause"
        })
      }), isPaused && hasResumeFeature && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        label: __('Resume', 'simply-static'),
        className: "ss-generate-media-button",
        showToolTip: true,
        onClick: () => resumeExport(),
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "controls-play"
        })
      }), hasCancelFeature && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        onClick: () => cancelExport(),
        label: __('Cancel', 'simply-static'),
        className: "ss-generate-cancel-button",
        showToolTip: true,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: 'no'
        })
      })]
    }), children]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (GenerateButtons);

/***/ }),

/***/ "./src/settings/components/HelperVideo.jsx":
/*!*************************************************!*\
  !*** ./src/settings/components/HelperVideo.jsx ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react_player__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react-player */ "./node_modules/react-player/dist/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);




const {
  __
} = wp.i18n;
function HelperVideo({
  title,
  videoUrl
}) {
  const [isVideoModalOpen, setVideoModalOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const openVideoModal = () => setVideoModalOpen(true);
  const closeVideoModal = () => setVideoModalOpen(false);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
    children: [isVideoModalOpen && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
      className: "simply-static-video-modal-background",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Modal, {
        title: title,
        className: 'simply-static-video-modal',
        onRequestClose: closeVideoModal,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(react_player__WEBPACK_IMPORTED_MODULE_2__["default"], {
          src: videoUrl,
          controls: true,
          width: '920px',
          height: '560px',
          playsInline: true
        })
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
      variant: 'link',
      className: "simply-static-video-button",
      onClick: openVideoModal,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
        icon: 'format-video'
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (HelperVideo);

/***/ }),

/***/ "./src/settings/components/Integration.jsx":
/*!*************************************************!*\
  !*** ./src/settings/components/Integration.jsx ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _HelperVideo__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./HelperVideo */ "./src/settings/components/HelperVideo.jsx");
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);





const {
  __
} = wp.i18n;
function Integration({
  integration,
  settings,
  toggleIntegration
}) {
  const {
    isQueuedIntegration
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  let isActive = integration.active;
  const isPro = integration.pro;
  const canRun = integration.can_run;
  const alwaysActive = integration.always_active;
  const isQueued = isQueuedIntegration(integration.id);
  if (typeof settings.integrations !== 'undefined' && settings.integrations !== false) {
    isActive = settings.integrations.indexOf(integration.id) >= 0;
  }
  let canUse = options.plan === 'pro' || !isPro;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
      className: 'ss-integration',
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("strong", {
          children: [integration.name || integration.id, canRun && isQueued && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("em", {
            className: "ss-text-notice",
            children: __('Requires saving settings', 'simply-static')
          }), integration.id === 'redirection' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_HelperVideo__WEBPACK_IMPORTED_MODULE_1__["default"], {
            title: __('Automated Redirects with Redirection', 'simply-static'),
            videoUrl: 'https://youtu.be/sS4BQcZ4dN8'
          }), integration.id === 'complianz' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_HelperVideo__WEBPACK_IMPORTED_MODULE_1__["default"], {
            title: __('Cookie Consent with Complianz', 'simply-static'),
            videoUrl: 'https://youtu.be/GPKYtt8A5QE'
          })]
        }), integration.description !== '' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("br", {}), integration.description]
        })]
      }), !canRun && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("span", {
        className: 'ss-align-right ss-no-shrink',
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("em", {
          children: "Missing Plugin"
        }), !canUse && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
            variant: "link",
            href: "https://simplystatic.com/pricing/",
            children: __('Requires Simply Static Pro', 'simply-static')
          })
        })]
      }), canRun && canUse && !alwaysActive && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
        className: 'integration-toggle',
        __nextHasNoMarginBottom: true,
        checked: isActive,
        onChange: value => {
          toggleIntegration(integration.id, value);
        }
      }), canRun && canUse && alwaysActive && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("em", {
        children: "Always Active"
      }), canRun && !canUse && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        variant: "primary",
        href: "https://simplystatic.com/pricing/",
        children: __('Get the Pro version', 'simply-static')
      })]
    })
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Integration);

/***/ }),

/***/ "./src/settings/components/LogButtons.jsx":
/*!************************************************!*\
  !*** ./src/settings/components/LogButtons.jsx ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);




const {
  __
} = wp.i18n;
function LogButtons() {
  const [logDeleted, setLogDeleted] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const deleteLog = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/delete-log',
      method: 'POST'
    });
    setLogDeleted(true);
    setTimeout(function () {
      setLogDeleted(false);
    }, 2000);
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
      variant: "primary",
      href: options.log_file,
      download: true,
      style: {
        marginRight: "10px"
      },
      children: __('Download Log', 'simply-static')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
      variant: "secondary",
      onClick: deleteLog,
      children: __('Clear Log', 'simply-static')
    }), logDeleted && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
      type: "slide-in",
      options: {
        origin: 'top'
      },
      children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
        status: "success",
        isDismissible: false,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("p", {
          children: __('Log file cleared.', 'simply-static')
        })
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (LogButtons);

/***/ }),

/***/ "./src/settings/components/SettingsPage.jsx":
/*!**************************************************!*\
  !*** ./src/settings/components/SettingsPage.jsx ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _pages_GeneralSettings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../pages/GeneralSettings */ "./src/settings/pages/GeneralSettings.jsx");
/* harmony import */ var _pages_Diagnostics__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../pages/Diagnostics */ "./src/settings/pages/Diagnostics.jsx");
/* harmony import */ var _pages_Utilities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../pages/Utilities */ "./src/settings/pages/Utilities.jsx");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _pages_DeploymentSettings__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../pages/DeploymentSettings */ "./src/settings/pages/DeploymentSettings.jsx");
/* harmony import */ var _pages_FormSettings__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../pages/FormSettings */ "./src/settings/pages/FormSettings.jsx");
/* harmony import */ var _pages_SearchSettings__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../pages/SearchSettings */ "./src/settings/pages/SearchSettings.jsx");
/* harmony import */ var _pages_DebugSettings__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../pages/DebugSettings */ "./src/settings/pages/DebugSettings.jsx");
/* harmony import */ var _pages_IntegrationsSettings__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../pages/IntegrationsSettings */ "./src/settings/pages/IntegrationsSettings.jsx");
/* harmony import */ var _pages_Generate__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../pages/Generate */ "./src/settings/pages/Generate.jsx");
/* harmony import */ var _pages_Optimize__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../pages/Optimize */ "./src/settings/pages/Optimize.jsx");
/* harmony import */ var _pages_Workflow__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../pages/Workflow */ "./src/settings/pages/Workflow.jsx");
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _SidebarMultisite__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./SidebarMultisite */ "./src/settings/components/SidebarMultisite.jsx");
/* harmony import */ var _SidebarSite__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./SidebarSite */ "./src/settings/components/SidebarSite.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__);

















const {
  __
} = wp.i18n;
function SettingsPage() {
  const {
    isRunning,
    isPaused,
    passedChecks,
    isPro,
    showMobileNav,
    setShowMobileNav
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_13__.SettingsContext);
  const [activeItem, setActiveItem] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)({
    activeItem: "/"
  });
  const [initialPage, setInitialPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(localStorage.getItem('ss-initial-page') ? localStorage.getItem('ss-initial-page') : options.initial);
  const [initialSet, setInitialSet] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    // Change initial page.
    let initialPageRedirect = localStorage.getItem('ss-initial-page');
    if (!initialSet) {
      setInitialSet(true);
      if (initialPageRedirect) {
        setActiveItem(initialPageRedirect);
        setInitialPage(initialPageRedirect);
        localStorage.removeItem('ss-initial-page');
      } else {
        setActiveItem(options.initial);
        setInitialPage(options.initial);
      }
    }
  }, [options, isRunning, isPaused]);
  const minHeight = () => {
    return window.innerHeight - (wpadminbar ? wpadminbar.clientHeight : 0) - 1;
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)("div", {
    className: "plugin-settings-container",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorProvider, {
      initialPath: initialPage,
      style: {
        minHeight: minHeight() + "px"
      },
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Flex, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsxs)("a", {
          onClick: () => {
            setShowMobileNav(!showMobileNav);
          },
          className: "show-nav",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Dashicon, {
            icon: "align-center"
          }), " ", __('Toggle menu', 'simply-static')]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.FlexItem, {
          className: showMobileNav ? 'toggle-nav sidebar' : 'sidebar',
          children: options.is_network ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_SidebarMultisite__WEBPACK_IMPORTED_MODULE_14__["default"], {}) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_SidebarSite__WEBPACK_IMPORTED_MODULE_15__["default"], {
            setActiveItem: setActiveItem,
            activeItem: activeItem
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.FlexItem, {
          isBlock: true,
          className: !showMobileNav ? 'toggle-nav' : '',
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsxs)("div", {
            className: "plugin-settings",
            children: ['no' === passedChecks && !options.is_network ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Animate, {
              type: "slide-in",
              options: {
                origin: 'top'
              },
              children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Notice, {
                status: "notice",
                isDismissible: false,
                className: activeItem == '/' ? 'diagnostics-notice diagnostics-notice-generate' : 'diagnostics-notice',
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsxs)("p", {
                  children: [__('There are errors in diagnostics that may negatively affect your static export.', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)("br", {}), __('Please review them and get them fixed to avoid problems.', 'simply-static')]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorButton, {
                  isSecondary: true,
                  onClick: () => {
                    setActiveItem('/diagnostics');
                    setShowMobileNav(!showMobileNav);
                  },
                  className: activeItem === '/diagnostics' ? 'is-active-item' : '',
                  path: "/diagnostics",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Dashicon, {
                    icon: "editor-help"
                  }), " ", __('Visit Diagnostics', 'simply-static')]
                })]
              })
            }) : '', 'pro' === options.plan && !isPro() ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Animate, {
              type: "slide-in",
              options: {
                origin: 'top'
              },
              children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.Fragment, {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Notice, {
                  status: "error",
                  isDismissible: false,
                  className: activeItem == '/' ? 'diagnostics-notice diagnostics-notice-generate' : 'diagnostics-notice',
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsxs)("p", {
                    children: [__('You are using the pro version without a valid license.', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)("br", {}), __('We have temporarily disabled all the pro features now. Please contact our support to have the problem solved.', 'simply-static')]
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
                    isPrimary: true,
                    href: "https://simplystatic.com/support/",
                    target: "_blank",
                    children: "Contact Support"
                  })]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalSpacer, {
                  margin: "5px"
                })]
              })
            }) : '', activeItem === '/' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_Generate__WEBPACK_IMPORTED_MODULE_10__["default"], {})
            }), activeItem === '/diagnostics' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/diagnostics",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_Diagnostics__WEBPACK_IMPORTED_MODULE_1__["default"], {})
            }), activeItem === '/general' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/general",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_GeneralSettings__WEBPACK_IMPORTED_MODULE_0__["default"], {})
            }), activeItem === '/deployment' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/deployment",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_DeploymentSettings__WEBPACK_IMPORTED_MODULE_5__["default"], {})
            }), activeItem === '/forms' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/forms",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_FormSettings__WEBPACK_IMPORTED_MODULE_6__["default"], {})
            }), activeItem === '/search' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/search",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_SearchSettings__WEBPACK_IMPORTED_MODULE_7__["default"], {})
            }), activeItem === '/optimize' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/optimize",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_Optimize__WEBPACK_IMPORTED_MODULE_11__["default"], {})
            }), activeItem === '/workflow' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/workflow",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_Workflow__WEBPACK_IMPORTED_MODULE_12__["default"], {})
            }), activeItem === '/utilities' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/utilities",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_Utilities__WEBPACK_IMPORTED_MODULE_2__["default"], {})
            }), activeItem === '/debug' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/debug",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_DebugSettings__WEBPACK_IMPORTED_MODULE_8__["default"], {})
            }), activeItem === '/integrations' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalNavigatorScreen, {
              path: "/integrations",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_16__.jsx)(_pages_IntegrationsSettings__WEBPACK_IMPORTED_MODULE_9__["default"], {})
            })]
          })
        })]
      })
    })
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SettingsPage);

/***/ }),

/***/ "./src/settings/components/SidebarMultisite.jsx":
/*!******************************************************!*\
  !*** ./src/settings/components/SidebarMultisite.jsx ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _VersionInfo__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./VersionInfo */ "./src/settings/components/VersionInfo.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);





const {
  __
} = wp.i18n;
function SidebarMultisite(props = null) {
  const {
    isPro
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
    className: "plugin-nav",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
      className: "plugin-logo",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("img", {
        alt: "Logo",
        src: options.logo
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_VersionInfo__WEBPACK_IMPORTED_MODULE_3__["default"], {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
      href: "https://simplystatic.com/changelogs/",
      target: "_blank",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
        icon: "editor-ul"
      }), " ", __('Changelog', 'simply-static')]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
      href: "https://docs.simplystatic.com",
      target: "_blank",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
        icon: "admin-links"
      }), " ", __('Documentation', 'simply-static')]
    }), 'free' === options.plan && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
      href: "https://simplystatic.com",
      target: "_blank",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
        icon: "admin-site-alt3"
      }), "Simply Static Pro"]
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SidebarMultisite);

/***/ }),

/***/ "./src/settings/components/SidebarSite.jsx":
/*!*************************************************!*\
  !*** ./src/settings/components/SidebarSite.jsx ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _VersionInfo__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./VersionInfo */ "./src/settings/components/VersionInfo.jsx");
/* harmony import */ var _GenerateButtons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./GenerateButtons */ "./src/settings/components/GenerateButtons.jsx");
/* harmony import */ var _EnvironmentSidebar__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./EnvironmentSidebar */ "./src/settings/components/EnvironmentSidebar.jsx");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _hooks_useInterval__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../hooks/useInterval */ "./src/hooks/useInterval.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__);









const {
  __
} = wp.i18n;
function SidebarSite(props = null) {
  const {
    isRunning,
    setIsRunning,
    isResumed,
    setIsResumed,
    isPaused,
    setIsPaused,
    blogId,
    settings,
    updateFromNetwork,
    getSettings,
    isPro,
    canRunIntegration,
    showMobileNav,
    setShowMobileNav,
    isDelayed
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  const {
    activeItem,
    setActiveItem
  } = props;
  const [disabledButton, setDisabledButton] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [selectedCopySite, setSelectedCopySite] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('current');
  const [selectablesSites, setSelectableSites] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [isUpdatingFromNetwork, setIsUpdatingFromNetwork] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [selectedExportType, setSelectedExportType] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('export');
  const [canRunExport, setCanRunExport] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  if (options.is_multisite) {
    const checkIfCanRun = () => {
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default()({
        path: '/simplystatic/v1/check-can-run',
        method: 'GET'
      }).then(resp => {
        var json = JSON.parse(resp);
        if (json.can_run) {
          setCanRunExport(true);
        } else {
          setCanRunExport(false);
        }
      });
    };
    (0,_hooks_useInterval__WEBPACK_IMPORTED_MODULE_7__["default"])(() => {
      checkIfCanRun();
    }, isRunning ? null : 100000);
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
      checkIfCanRun();
    }, []);
  }
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setDisabledButton(isRunning || isPaused);
    if (options.selectable_sites && !options.is_network && options.is_multisite) {
      let sites = options.selectable_sites.slice().sort(function (a, b) {
        return (a.name || '').localeCompare(b.name || '');
      }).map(function (site) {
        return {
          label: `${site.name} (#${site.blog_id})`,
          value: site.blog_id
        };
      });
      sites.unshift({
        label: __('Use current settings', 'simply-static'),
        value: 'current'
      });
      setSelectableSites(sites);
    }
  }, [options, isRunning, isPaused]);

  // Set the default export type when the component mounts or when settings change
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Always use 'export' as the default option
    setSelectedExportType('export');
  }, [settings]);
  const startExport = () => {
    setDisabledButton(true);
    setIsResumed(false);
    setIsPaused(false);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default()({
      path: '/simplystatic/v1/start-export',
      method: 'POST',
      data: {
        'blog_id': blogId,
        'type': selectedExportType
      }
    }).then(resp => {
      var json = JSON.parse(resp);
      if (json.status === 500) {
        alert(json.message);
        setDisabledButton(false);
        return;
      }
      setIsRunning(true);
    });
  };
  const cancelExport = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default()({
      path: '/simplystatic/v1/cancel-export',
      method: 'POST',
      data: {
        'blog_id': blogId
      }
    }).then(resp => {
      setIsResumed(false);
      setIsPaused(false);
      setIsRunning(false);
      setDisabledButton(false);
    });
  };
  const pauseExport = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default()({
      path: '/simplystatic/v1/pause-export',
      method: 'POST',
      data: {
        'blog_id': blogId
      }
    }).then(resp => {
      setIsRunning(false);
      setIsResumed(false);
      setIsPaused(true);
    });
  };
  const resumeExport = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default()({
      path: '/simplystatic/v1/resume-export',
      method: 'POST',
      data: {
        'blog_id': blogId
      }
    }).then(resp => {
      setIsResumed(true);
      setIsPaused(false);
      setIsRunning(true);
    });
  };
  const runUpdateFromNetwork = blogId => {
    // Update settings from selected blog_id.
    updateFromNetwork(blogId);
    setIsUpdatingFromNetwork(true);
    setTimeout(function () {
      setIsUpdatingFromNetwork(false);
      window.location.reload();
    }, 3500);
  };
  let buildOptions = '';
  if (Object.keys(options.builds).length) {
    const builds = Object.keys(options.builds).map(id => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("option", {
      value: id,
      children: options.builds[id]
    }, id));

    // Sort builds alphabetically
    builds.sort((a, b) => {
      return a.props.children.localeCompare(b.props.children);
    });
    buildOptions = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("optgroup", {
      label: "Builds",
      children: builds
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
    className: "plugin-nav",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
      className: "plugin-logo",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("img", {
        alt: "Logo",
        src: options.logo
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_VersionInfo__WEBPACK_IMPORTED_MODULE_3__["default"], {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
      className: `generate-container ${disabledButton ? 'generating' : ''}`,
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
        className: 'generate-type',
        value: selectedExportType,
        disabled: disabledButton,
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true,
        onChange: value => {
          setSelectedExportType(value);
        },
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("option", {
          value: "export",
          children: __('Export', 'simply-static')
        }), 'zip' !== settings.delivery_method && 'tiiny' !== settings.delivery_method && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
          children: 'pro' === options.plan && isPro() ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("option", {
            value: "update",
            children: __('Export Changes', 'simply-static')
          }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("option", {
            disabled: true,
            value: "update",
            children: __('Export Changes (Requires Simply Static Pro)', 'simply-static')
          })
        }), buildOptions]
      }), canRunExport && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_GenerateButtons__WEBPACK_IMPORTED_MODULE_4__["default"], {
        canGenerate: !disabledButton,
        startExport: startExport,
        cancelExport: cancelExport,
        pauseExport: pauseExport,
        resumeExport: resumeExport,
        isRunning: isRunning,
        isPaused: isPaused,
        isResumed: isResumed,
        isDelayed: isDelayed
      }), !canRunExport && options.is_multisite && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          disabled: true,
          className: 'generate',
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
            icon: "update"
          }), __('Generate', 'simply-static')]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          target: '_blank',
          variant: 'link',
          href: "https://simplystatic.com/pricing/",
          children: __('An export from another site is running. Upgrade to queue them.', 'simply-static')
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
      children: ['pro' === options.plan && isPro() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
        children: !options.is_network && canRunIntegration('environments') && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_EnvironmentSidebar__WEBPACK_IMPORTED_MODULE_5__["default"], {
          isRunning: isRunning,
          getSettings: getSettings
        })
      }), !options.is_network && options.is_multisite && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
        className: "import-container",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("h4", {
          className: "settings-headline",
          children: [" ", __('Import', 'simply-static')]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
          value: selectedCopySite,
          options: selectablesSites,
          help: __('Choose a subsite to import settings from.', 'simply-static'),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          onChange: blog_id => {
            setSelectedCopySite(blog_id);
          }
        }), selectedCopySite !== 'current' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          isPrimary: true,
          onClick: () => {
            runUpdateFromNetwork(selectedCopySite);
          },
          children: __('Import Settings', 'simply-static')
        }), isUpdatingFromNetwork ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
          type: "slide-in",
          options: {
            origin: 'top'
          },
          children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: "success",
            isDismissible: false,
            className: "upgrade-network-notice",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
              children: __('Settings successfully imported.', 'simply-static')
            })
          })
        }) : '']
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("h4", {
        className: "settings-headline",
        children: [" ", __('Tools', 'simply-static')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
        onClick: () => {
          setActiveItem('/');
          setShowMobileNav(!showMobileNav);
        },
        className: activeItem === '/' ? 'is-active-item generate' : 'generate',
        path: "/",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "update"
        }), " ", __('Activity Log', 'simply-static')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
        onClick: () => {
          setActiveItem('/diagnostics');
          setShowMobileNav(!showMobileNav);
        },
        className: activeItem === '/diagnostics' ? 'is-active-item' : '',
        path: "/diagnostics",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "bell"
        }), " ", __('Diagnostics', 'simply-static')]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("h4", {
        className: "settings-headline",
        children: [" ", __('Settings', 'simply-static')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
        onClick: () => {
          setActiveItem('/general');
          setShowMobileNav(!showMobileNav);
        },
        className: activeItem === '/general' ? 'is-active-item' : '',
        path: "/general",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "admin-generic"
        }), " ", __('General', 'simply-static')]
      }), !options.is_network && !options.hidden_settings.includes('deployment') && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
        onClick: () => {
          setActiveItem('/deployment');
          setShowMobileNav(!showMobileNav);
        },
        className: activeItem === '/deployment' ? 'is-active-item' : '',
        path: "/deployment",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "migrate"
        }), " ", __('Deploy', 'simply-static')]
      }), !options.is_network && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
          onClick: () => {
            setActiveItem('/forms');
            setShowMobileNav(!showMobileNav);
          },
          className: activeItem === '/forms' ? 'is-active-item' : '',
          path: "/forms",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
            icon: "align-center"
          }), " ", __('Forms', 'simply-static')]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
          onClick: () => {
            setActiveItem('/search');
            setShowMobileNav(!showMobileNav);
          },
          className: activeItem === '/search' ? 'is-active-item' : '',
          path: "/search",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
            icon: "search"
          }), " ", __('Search', 'simply-static')]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
          onClick: () => {
            setActiveItem('/optimize');
            setShowMobileNav(!showMobileNav);
          },
          className: activeItem === '/optimize' ? 'is-active-item' : '',
          path: "/optimize",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
            icon: "dashboard"
          }), " ", __('Optimize', 'simply-static')]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
          onClick: () => {
            setActiveItem('/workflow');
            setShowMobileNav(!showMobileNav);
          },
          className: activeItem === '/workflow' ? 'is-active-item' : '',
          path: "/workflow",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
            icon: "randomize"
          }), " ", __('Workflow', 'simply-static')]
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("h4", {
        className: "settings-headline",
        children: [" ", __('Advanced', 'simply-static')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
        onClick: () => {
          setActiveItem('/integrations');
          setShowMobileNav(!showMobileNav);
        },
        className: activeItem === '/integrations' ? 'is-active-item' : '',
        path: "/integrations",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "block-default"
        }), " ", __('Integrations', 'simply-static')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
        onClick: () => {
          setActiveItem('/utilities');
          setShowMobileNav(!showMobileNav);
        },
        className: activeItem === '/utilities' ? 'is-active-item' : '',
        path: "/utilities",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "admin-tools"
        }), " ", __('Utilities', 'simply-static')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNavigatorButton, {
        onClick: () => {
          setActiveItem('/debug');
          setShowMobileNav(!showMobileNav);
        },
        className: activeItem === '/debug' ? 'is-active-item' : '',
        path: "/debug",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "editor-help"
        }), " ", __('Debug', 'simply-static')]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("h4", {
        className: "settings-headline",
        children: "Learn"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        href: "https://docs.simplystatic.com",
        target: "_blank",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "admin-links"
        }), " ", __('Documentation', 'simply-static')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        href: "https://www.youtube.com/playlist?list=PLcpe8_rNg8U5g1gCOa0Ge6T17f50nSvmg",
        target: "_blank",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "format-video"
        }), " ", __('Video Course', 'simply-static')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        href: "https://simplystatic.com/tutorials/",
        target: "_blank",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
          icon: "edit"
        }), " ", __('Tutorials', 'simply-static')]
      })]
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SidebarSite);

/***/ }),

/***/ "./src/settings/components/Site.jsx":
/*!******************************************!*\
  !*** ./src/settings/components/Site.jsx ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _GenerateButtons__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./GenerateButtons */ "./src/settings/components/GenerateButtons.jsx");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);






const {
  __
} = wp.i18n;
function Site(props) {
  const {
    site,
    setAnyRunning
  } = props;
  const blogId = site.id;
  const [canGenerate, setCanGenerate] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(!site.running && !site.paused);
  const [isRunning, setIsRunning] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(site.running);
  const [isPaused, setIsPaused] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(site.paused);
  (0,react__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    if (isRunning) {
      setAnyRunning(true);
    }
  }, [isRunning]);
  (0,react__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    setIsPaused(site.paused);
    setIsRunning(site.running);
    setCanGenerate(!site.running && !site.paused);
  }, [site]);
  const startExport = () => {
    setCanGenerate(false);
    setIsPaused(false);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: '/simplystatic/v1/start-export',
      method: 'POST',
      data: {
        'blog_id': blogId,
        'type': 'export',
        'is_network_admin': options.is_network
      }
    }).then(resp => {
      var json = JSON.parse(resp);
      if (json.status === 500) {
        setCanGenerate(true);
        return;
      }
      setIsRunning(true);
    });
  };
  const cancelExport = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: '/simplystatic/v1/cancel-export',
      method: 'POST',
      data: {
        'blog_id': blogId,
        'is_network_admin': options.is_network
      }
    }).then(resp => {
      setIsPaused(false);
      setIsRunning(false);
      setCanGenerate(true);
    });
  };
  const pauseExport = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: '/simplystatic/v1/pause-export',
      method: 'POST',
      data: {
        'blog_id': blogId,
        'is_network_admin': options.is_network
      }
    }).then(resp => {
      setIsRunning(false);
      setIsPaused(true);
    });
  };
  const resumeExport = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: '/simplystatic/v1/resume-export',
      method: 'POST',
      data: {
        'blog_id': blogId,
        'is_network_admin': options.is_network
      }
    }).then(resp => {
      setIsPaused(false);
      setIsRunning(true);
    });
  };
  const canRunFromNetwork = () => {
    return options.plan === 'pro';
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("tr", {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("td", {
      children: [site.name, /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("a", {
        href: site.url,
        children: site.url
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("td", {
      children: site.status
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("td", {
      className: 'generate-container network-generate-container',
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_GenerateButtons__WEBPACK_IMPORTED_MODULE_1__["default"], {
        site: site,
        canGenerate: canGenerate,
        startExport: canRunFromNetwork() ? startExport : null,
        isPaused: isPaused,
        isRunning: isRunning,
        cancelExport: canRunFromNetwork() ? cancelExport : null,
        pauseExport: canRunFromNetwork() ? pauseExport : null,
        resumeExport: canRunFromNetwork() ? resumeExport : null,
        children: [!canRunFromNetwork() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
          label: 'Generate',
          showTooltip: true,
          className: 'generate',
          disabled: true,
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Dashicon, {
            icon: "update"
          }), __('Generate', 'simply-static')]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
          className: "sub-site-settings",
          onClick: () => window.location = site.settings_url,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Dashicon, {
            icon: "admin-generic"
          })
        }), !canRunFromNetwork() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
          target: '_blank',
          variant: 'link',
          href: "https://simplystatic.com/pricing/",
          children: __('Upgrade to manage from network.', 'simply-static')
        })]
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Site);

/***/ }),

/***/ "./src/settings/components/Sites.jsx":
/*!*******************************************!*\
  !*** ./src/settings/components/Sites.jsx ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _hooks_useInterval__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../hooks/useInterval */ "./src/hooks/useInterval.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _Site__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Site */ "./src/settings/components/Site.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);






const {
  __
} = wp.i18n;
function Sites(props) {
  const [sites, setSites] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [anyRunning, setAnyRunning] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [siteToTriggerCron, setSiteToTriggerCron] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(0);
  const triggerCron = blogId => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: '/simplystatic/v1/trigger-cron',
      method: 'POST',
      data: {
        'blog_id': blogId
      }
    }).then(resp => {
      var json = JSON.parse(resp);
      if (json.status === 200) {
        // Show success message or update UI
        console.log('CRON triggered successfully for site ' + blogId);
      } else {
        console.error('Failed to trigger CRON:', json.message);
      }
      let id = getNextSiteId(blogId);
      setSiteToTriggerCron(id);
    }).catch(error => {
      console.error('Error triggering CRON:', error);
    });
  };
  function getNextSiteId(siteId) {
    let ids = getSiteIds();
    if (ids.length === 0) {
      return 0;
    }
    let index = ids.indexOf(siteId);
    if (index === -1) {
      return ids[0];
    }
    index++;
    let id = ids[index] || ids[0];
    return id;
  }
  function getSiteIds() {
    let siteIds = [];
    sites.forEach(function (site) {
      siteIds.push(site.id);
    });
    return siteIds;
  }
  function refreshSites() {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: '/simplystatic/v1/sites',
      method: 'GET'
    }).then(resp => {
      let sitesObjects = [];
      let haveRunningSite = false;
      resp.data.forEach(function (site) {
        sitesObjects.push(site);
        if (site.running) {
          haveRunningSite = true;
        }
      });
      setSites(sitesObjects);
      setAnyRunning(haveRunningSite);
    });
  }
  (0,_hooks_useInterval__WEBPACK_IMPORTED_MODULE_2__["default"])(() => {
    refreshSites();
  }, anyRunning ? 2500 : 300000); // Any running, check every 2-3secs. Not running, check every 5 mins.

  (0,_hooks_useInterval__WEBPACK_IMPORTED_MODULE_2__["default"])(() => {
    if (!siteToTriggerCron && sites.length > 0) {
      setSiteToTriggerCron(sites[0].id);
    }
    if (!siteToTriggerCron) {
      return;
    }
    triggerCron(siteToTriggerCron);
  }, anyRunning ? 150000 : null); // Run Cron every 2.5mins. 1 site per iteration.

  (0,react__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    refreshSites();
  }, []);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("table", {
      className: 'wp-list-table widefat fixed striped posts simple-static-sites',
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("thead", {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("tr", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("th", {
            children: __('Name', 'simply-static')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("th", {
            children: __('Status', 'simply-static')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("th", {
            children: __('Actions', 'simply-static')
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("tbody", {
        children: sites.map(site => {
          return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_Site__WEBPACK_IMPORTED_MODULE_4__["default"], {
            setAnyRunning: setAnyRunning,
            site: site
          }, site.id);
        })
      })]
    })
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Sites);

/***/ }),

/***/ "./src/settings/components/VersionInfo.jsx":
/*!*************************************************!*\
  !*** ./src/settings/components/VersionInfo.jsx ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



function VersionInfo() {
  const {
    isPro,
    isStudio
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_0__.SettingsContext);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.Fragment, {
    children: 'pro' === options.plan && isPro() ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.Fragment, {
      children: isStudio() ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("p", {
        className: "version-number",
        children: ["Free: ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("b", {
          children: options.version
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("br", {}), "Pro: ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("b", {
          children: options.version_pro
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("br", {}), "Studio: ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("b", {
          children: options.version_studio
        })]
      }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("p", {
        className: "version-number",
        children: ["Free: ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("b", {
          children: options.version
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("br", {}), "Pro: ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("b", {
          children: options.version_pro
        })]
      })
    }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("p", {
      className: "version-number",
      children: ["Version: ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("b", {
        children: options.version
      })]
    })
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (VersionInfo);

/***/ }),

/***/ "./src/settings/context/SettingsContext.jsx":
/*!**************************************************!*\
  !*** ./src/settings/context/SettingsContext.jsx ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   SettingsContext: () => (/* binding */ SettingsContext),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _hooks_useInterval__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../hooks/useInterval */ "./src/hooks/useInterval.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);




const {
  __
} = wp.i18n;
const SettingsContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)();
function SettingsContextProvider(props) {
  const [isRunning, setIsRunning] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [isDelayed, setIsDelayed] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  const [isPaused, setIsPaused] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [isResumed, setIsResumed] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [settingsSaved, setSettingsSaved] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [settings, setSettings] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [configs, setConfigs] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [passedChecks, setPassedChecks] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('yes');
  const [blogId, setBlogId] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(1);
  const [queuedIntegrations, setQueuedIntegrations] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [showMobileNav, setShowMobileNav] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(true);
  const getSettings = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/settings'
    }).then(options => {
      setSettings(options);
    });
  };
  const saveSettings = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/settings',
      method: 'POST',
      data: settings
    }).then(resp => {
      setQueuedIntegrations([]);
    });
  };
  const resetSettings = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/settings/reset',
      method: 'POST'
    }).then(resp => {
      // Parse the response to get the default settings
      const response = JSON.parse(resp);
      if (response.status === 200 && response.data) {
        // Update the settings state with the default settings from the server
        setSettings(response.data);
      }
    });
  };
  const resetDatabase = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/settings/reset-database',
      method: 'POST'
    });
  };
  const resetBackgroundQueue = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/settings/reset-background-queue',
      method: 'POST'
    });
  };
  const updateFromNetwork = blogId => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/update-from-network',
      method: 'POST',
      data: {
        'blog_id': blogId
      }
    });
  };
  const checkIfRunning = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/is-running',
      method: 'GET'
    }).then(resp => {
      var json = JSON.parse(resp);
      setIsRunning(json.running);
      setIsPaused(json.paused);
      if (json.delayed) {
        setIsDelayed(json.delayed_until);
      }
    });
  };
  const importSettings = newSettings => {
    setSettings(newSettings);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/settings',
      method: 'POST',
      data: newSettings
    });
  };
  const migrateSettings = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/migrate',
      method: 'POST',
      migrate: true
    });
  };
  const updateSetting = (key, value) => {
    const updatedSettings = {
      ...settings,
      [key]: value
    };
    setSettings(updatedSettings);
  };
  const getStatus = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/system-status'
    }).then(configs => {
      setConfigs(configs);
      getStatusPassed();
    });
  };
  const getStatusPassed = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/system-status/passed'
    }).then(result => {
      let test = JSON.parse(result);
      setPassedChecks(test.passed);
    });
  };
  const resetDiagnostics = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/reset-diagnostics',
      method: 'POST'
    });
  };
  const isPro = () => {
    if (options.is_multisite) {
      return true;
    }
    if (options.connect) {
      return !!options.connect.is_connected;
    }
    return false;
  };
  const isStudio = () => {
    if (options.home.includes('static.studio') || options.home.includes('static1.studio') || options.home.includes('static2.studio')) {
      return true;
    }
    return false;
  };
  const integrationRequiresSaving = integration => {
    /**
     * @todo make it defined inside integration classes when more come.
     * @type {string[]}
     */
    const integrations = ['environments'];
    return integrations.indexOf(integration) >= 0;
  };
  const maybeQueueIntegration = integration => {
    if (!integrationRequiresSaving(integration)) {
      return;
    }

    // Already queued.
    if (isQueuedIntegration(integration)) {
      return;
    }
    queuedIntegrations.push(integration);
    setQueuedIntegrations(queuedIntegrations);
  };
  const maybeUnqueueIntegration = integration => {
    if (!integrationRequiresSaving(integration)) {
      return;
    }

    // Already queued.
    if (!isQueuedIntegration(integration)) {
      return;
    }
    const index = queuedIntegrations.indexOf(integration);
    if (index < 0) {
      return;
    }
    queuedIntegrations.splice(index, 1);
    setQueuedIntegrations(queuedIntegrations);
  };
  const canRunIntegration = integration => {
    if (!isIntegrationActive(integration)) {
      return false;
    }
    if (isQueuedIntegration(integration)) {
      return false;
    }
    return true;
  };
  const isQueuedIntegration = integration => {
    return queuedIntegrations.indexOf(integration) >= 0;
  };
  const isIntegrationActive = integration => {
    let integrations = settings.integrations;
    if (false === integrations || !integrations || !Array.isArray(integrations)) {
      return false;
    }
    if (integrations.indexOf(integration) >= 0) {
      return true;
    }
    return false;
  };
  (0,_hooks_useInterval__WEBPACK_IMPORTED_MODULE_2__["default"])(() => {
    setIsDelayed(isDelayed - 1);
  }, isDelayed > 0 ? 1000 : null);
  (0,_hooks_useInterval__WEBPACK_IMPORTED_MODULE_2__["default"])(() => {
    checkIfRunning();
  }, isRunning || isDelayed ? 5000 : null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    // If current_settings is available in the options object, use it instead of fetching from the API
    if (options.current_settings) {
      setSettings(options.current_settings);
    } else {
      // Fallback to fetching from the API if current_settings is not available
      getSettings();
    }
    getStatus();
    checkIfRunning();
    setBlogId(options.blog_id);
  }, []);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(SettingsContext.Provider, {
    value: {
      settings,
      configs,
      passedChecks,
      settingsSaved,
      setSettingsSaved,
      updateSetting,
      setSettings,
      saveSettings,
      resetSettings,
      resetDatabase,
      resetBackgroundQueue,
      getSettings,
      updateFromNetwork,
      importSettings,
      migrateSettings,
      resetDiagnostics,
      isRunning,
      setIsRunning,
      isPaused,
      setIsPaused,
      setIsResumed,
      isResumed,
      blogId,
      setBlogId,
      isPro,
      isStudio,
      isIntegrationActive,
      canRunIntegration,
      maybeQueueIntegration,
      maybeUnqueueIntegration,
      isQueuedIntegration,
      showMobileNav,
      setShowMobileNav,
      isDelayed
    },
    children: props.children
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SettingsContextProvider);

/***/ }),

/***/ "./src/settings/pages/DebugSettings.jsx":
/*!**********************************************!*\
  !*** ./src/settings/pages/DebugSettings.jsx ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/HelperVideo */ "./src/settings/components/HelperVideo.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);






const {
  __
} = wp.i18n;
function DebugSettings() {
  const {
    settings,
    updateSetting,
    saveSettings,
    settingsSaved,
    setSettingsSaved,
    isPro,
    isStudio
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_3__.SettingsContext);
  const [activateDebugLog, setActivateDebugLog] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [useServerCron, setUserServerCron] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [clearingTemp, setClearingTemp] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [clearNotice, setClearNotice] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const handleClearTemp = async () => {
    setClearingTemp(true);
    setClearNotice(null);
    try {
      const resp = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/simplystatic/v1/clear-temp-files',
        method: 'POST'
      });
      let data = resp;
      // Some endpoints in this app return JSON string, some parsed.
      if (typeof resp === 'string') {
        try {
          data = JSON.parse(resp);
        } catch (e) {/* ignore */
        }
      }
      if (data && data.status === 200) {
        setClearNotice({
          type: 'success',
          message: __('Temporary files cleared.', 'simply-static')
        });
      } else {
        const msg = data && data.message ? data.message : __('Could not clear temporary files.', 'simply-static');
        setClearNotice({
          type: 'error',
          message: msg
        });
      }
    } catch (e) {
      setClearNotice({
        type: 'error',
        message: __('Request failed. Please try again.', 'simply-static')
      });
    } finally {
      setClearingTemp(false);
    }
  };
  const setSavingSettings = () => {
    saveSettings();
    setSettingsSaved(true);
    setTimeout(function () {
      setSettingsSaved(false);
    }, 2000);
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (settings.debugging_mode) {
      setActivateDebugLog(settings.debugging_mode);
    }
    if (settings.server_cron) {
      setUserServerCron(settings.server_cron);
    }
  }, [settings]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("div", {
    className: "inner-settings",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
          children: [__('Basic Auth', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
            title: __('How to set up basic auth', 'simply-static'),
            videoUrl: 'https://youtu.be/6udSR3_zSOU'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
          children: __('If you\'ve secured WordPress with HTTP Basic Auth you need to specify the username and password to use below.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('Basic Auth Username', 'simply-static'),
          autoComplete: "off",
          type: "text",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: settings.http_basic_auth_username,
          onChange: username => {
            updateSetting('http_basic_auth_username', username);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('Basic Auth Password', 'simply-static'),
          type: "password",
          autoComplete: "off",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: settings.http_basic_auth_password,
          onChange: username => {
            updateSetting('http_basic_auth_password', username);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Enable Basic Auth', 'simply-static'),
          __nextHasNoMarginBottom: true,
          help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: 'free' === options.plan ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: __('Automatically setting up Basic Auth requires Simply Static Pro.', 'simply-static')
            }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: __('Once enabled we will put your entire website behind password protection.', 'simply-static')
            })
          }),
          disabled: 'free' === options.plan || !isPro(),
          checked: !!settings.http_basic_auth_on,
          onChange: value => {
            updateSetting('http_basic_auth_on', value);
          }
        }), settings.http_basic_auth_on && (!settings.http_basic_auth_username || !settings.http_basic_auth_password) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "warning",
          isDismissible: false,
          children: __('Requires Username & Password to work', 'simply-static')
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), !isStudio() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("b", {
          children: __('Temporary Files', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('Temporary Files Directory', 'simply-static'),
          type: "text",
          placeholder: options.temp_files_dir,
          help: __('Optionally specify the directory to save your temporary files. This directory must exist and be writeable.', 'simply-static'),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: settings.temp_files_dir,
          onChange: temp_dir => {
            updateSetting('temp_files_dir', temp_dir);
          }
        }), clearNotice && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: clearNotice.type,
            isDismissible: true,
            onRemove: () => setClearNotice(null),
            children: clearNotice.message
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
            margin: 5
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          isSecondary: true,
          onClick: handleClearTemp,
          disabled: clearingTemp,
          isBusy: clearingTemp,
          children: clearingTemp ? __('Clearing…', 'simply-static') : __('Clear Temporary Files', 'simply-static')
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("b", {
          children: __('Whitelist Plugins', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
          label: __('Whitelist plugins in diagnostics', 'simply-static'),
          placeholder: "autoptimize\nwp-search-with-algolia\nwp-rocket",
          help: __('If you want to exclude certain plugins from the diagnostics check add the plugin slugs here (one per line).', 'simply-static'),
          __nextHasNoMarginBottom: true,
          value: settings.whitelist_plugins,
          onChange: value => {
            updateSetting('whitelist_plugins', value);
          }
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), !isStudio() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("b", {
          children: __('Proxy Setup', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('Origin URL', 'simply-static'),
          type: "url",
          help: __('If the URL of your WordPress installation differs from the public-facing URL (Proxy Setup), add the public URL here.', 'simply-static'),
          placeholder: options.home,
          autoComplete: "off",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: settings.origin_url,
          onChange: origin_url => {
            updateSetting('origin_url', origin_url);
          }
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("b", {
          children: __('Debug Log', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Activate Debug Log', 'simply-static'),
          help: __('Enable it to download the debug log from Simply Static -> Generate.', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: activateDebugLog,
          onChange: value => {
            setActivateDebugLog(value);
            updateSetting('debugging_mode', value);
          }
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), !isStudio() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("b", {
          children: __('Cron', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Use server-side cron job', 'simply-static'),
          help: __('Enable this if you use a server-side cron job instead of the default WP-Cron.', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: useServerCron,
          onChange: value => {
            setUserServerCron(value);
            updateSetting('server_cron', value);
          }
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), settingsSaved && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
        type: "slide-in",
        options: {
          origin: 'top'
        },
        children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "success",
          isDismissible: false,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('Settings saved successfully.', 'simply-static')
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("div", {
      className: "save-settings",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        onClick: setSavingSettings,
        variant: "primary",
        children: __('Save Settings', 'simply-static')
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DebugSettings);

/***/ }),

/***/ "./src/settings/pages/DeploymentSettings.jsx":
/*!***************************************************!*\
  !*** ./src/settings/pages/DeploymentSettings.jsx ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/HelperVideo */ "./src/settings/components/HelperVideo.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);






const {
  __
} = wp.i18n;
function DeploymentSettings() {
  var _settings$github_batc;
  const {
    settings,
    updateSetting,
    saveSettings,
    settingsSaved,
    setSettingsSaved,
    isRunning,
    isPro,
    isStudio
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  const [deliveryMethod, setDeliveryMethod] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('zip');
  const [clearDirectory, setClearDirectory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [githubAccountType, setGithubAccountType] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('personal');
  const [githubVisibility, setGithubVisibility] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('private');
  const [emptyBucketBeforeExport, setEmptyBucketBeforeExport] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [throttleGitHubRequests, setThrottleGitHubRequests] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [region, setRegion] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('us-east-2');
  const [awsAuthMethod, setAwsAuthMethod] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('aws-iam-key');
  const [hasCopied, setHasCopied] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [pages, setPages] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [testDisabled, setTestDisabled] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [testRunning, setTestRunning] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [deploymentOptions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([{
    label: __('ZIP Archive', 'simply-static'),
    value: 'zip'
  }, {
    label: __('Local Directory', 'simply-static'),
    value: 'local'
  }, {
    label: __('Static Studio', 'simply-static'),
    value: 'simply-static-studio'
  }, {
    label: __('SFTP', 'simply-static'),
    value: 'sftp'
  }, {
    label: __('GitHub', 'simply-static'),
    value: 'github'
  }, {
    label: __('AWS S3', 'simply-static'),
    value: 'aws-s3'
  }, {
    label: __('Bunny CDN', 'simply-static'),
    value: 'cdn'
  }, {
    label: __('Tiiny.host', 'simply-static'),
    value: 'tiiny'
  }]);
  const setSavingSettings = () => {
    saveSettings();
    setSettingsSaved(true);
    setTestDisabled(false);
    setTimeout(function () {
      setSettingsSaved(false);
    }, 2000);
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (settings.delivery_method) {
      setDeliveryMethod(settings.delivery_method);
    }
    if (settings.clear_directory_before_export) {
      setClearDirectory(settings.clear_directory_before_export);
    }
    if (settings.github_account_type) {
      setGithubAccountType(settings.github_account_type);
    }
    if (settings.github_repository_visibility) {
      setGithubVisibility(settings.github_repository_visibility);
    }
    if (settings.github_repository_visibility) {
      setGithubVisibility(settings.github_repository_visibility);
    }
    if (settings.github_throttle_requests) {
      setThrottleGitHubRequests(settings.github_throttle_requests);
    }
    if (settings.aws_empty) {
      setEmptyBucketBeforeExport(settings.aws_empty);
    }
    if (settings.aws_auth_method) {
      setAwsAuthMethod(settings.aws_auth_method);
    }
    if (settings.aws_region) {
      setRegion(settings.aws_region);
    }

    // Get global page selection
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: '/simplystatic/v1/pages'
    }).then(fetched_pages => {
      let pages = fetched_pages;
      pages.unshift({
        label: __('No page selected', 'simply-static'),
        value: 0
      });
      setPages(pages);
    });
  }, [settings]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("div", {
    className: "inner-settings",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("b", {
          children: __('Deployment Settings', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
          children: __('Choose from a variety of deployment methods. Depending on your selection we either provide a ZIP file, export to a local directory or send your files to a remote destination.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
          label: __('Deployment method', 'simply-static'),
          value: deliveryMethod,
          options: deploymentOptions,
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          onChange: method => {
            setDeliveryMethod(method);
            updateSetting('delivery_method', method);
            setTestDisabled(true);
          }
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), deliveryMethod === 'simply-static-studio' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("b", {
          children: __('Static Studio', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
          children: __('The all-in-one Static WordPress cloud-hosting platform.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
          children: __('Enjoy secure WordPress, the fastest exports, and the best-performing static site hosting in one package.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("a", {
            className: "button button-primary",
            href: "https://simplystatic.com/simply-static-studio/",
            target: "_blank",
            children: "Check out Static Studio"
          })
        })]
      })]
    }), deliveryMethod === 'zip' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
          children: [__('ZIP', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
            title: __('How to export a ZIP file', 'simply-static'),
            videoUrl: 'https://youtu.be/WHaFjDte6zI'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
          children: __('Get a download link in the activity log once the static export has finished.', 'simply-static')
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), deliveryMethod === 'local' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
          children: [__('Local Directory', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
            title: __('How to deploy to a local directory', 'simply-static'),
            videoUrl: 'https://youtu.be/ZRdXQB5slnY'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('Path', 'simply-static'),
          type: "text",
          help: __("This is the directory where your static files will be saved. We will create it automatically on the first export if it doesn't exist.", 'simply-static'),
          placeholder: options.home_path + "public_static/",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: settings.local_dir,
          onChange: path => {
            updateSetting('local_dir', path);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          variant: "secondary",
          onClick: () => {
            try {
              navigator.clipboard.writeText(options.home_path);
              setHasCopied(true);
              setTimeout(() => setHasCopied(false), 1500);
            } catch (e) {
              console.error('Clipboard copy failed', e);
            }
          },
          children: hasCopied ? __('Copied home path', 'simply-static') : __('Copy home path', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          __nextHasNoMarginBottom: true,
          label: __('Clear Local Directory', 'simply-static'),
          help: clearDirectory ? __('Clear local directory before running an export.', 'simply-static') : __('Don\'t clear local directory before running an export.', 'simply-static'),
          checked: clearDirectory,
          onChange: value => {
            setClearDirectory(value);
            updateSetting('clear_directory_before_export', value);
          }
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
      children: [deliveryMethod === 'github' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
                children: [__('GitHub', 'simply-static'), " ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                  title: __('How to deploy to a GitHub (2/2)', 'simply-static'),
                  videoUrl: 'https://youtu.be/HqyTKwZuUAM'
                })]
              })
            }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                href: "https://simplystatic.com",
                children: [" ", __('Requires Simply Static Pro', 'simply-static')]
              })
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('GitHub enables you to export your static website to one of the common static hosting providers like Netlify, Cloudflare Pages or GitHub Pages.', 'simply-static')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
            label: __('Account Type', 'simply-static'),
            value: githubAccountType,
            help: __('Depending on the account type the settings fields will change.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            options: [{
              label: __('Personal', 'simply-static'),
              value: 'personal'
            }, {
              label: __('Organization', 'simply-static'),
              value: 'organization'
            }],
            onChange: type => {
              setGithubAccountType(type);
              updateSetting('github_account_type', type);
            }
          }), githubAccountType === 'organization' ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Organization', 'simply-static'),
            type: "text",
            help: __('Enter the name of your organization.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.github_user,
            onChange: organization => {
              updateSetting('github_user', organization);
            }
          }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Username', 'simply-static'),
            type: "text",
            help: __('Enter your GitHub username.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.github_user,
            onChange: name => {
              updateSetting('github_user', name);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('E-Mail', 'simply-static'),
            type: "email",
            help: __('Enter your GitHub email address. This will be used to commit files to your repository.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.github_email,
            onChange: email => {
              updateSetting('github_email', email);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: [__('Personal Access Token', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                title: __('How to prepare your GitHub account', 'simply-static'),
                videoUrl: 'https://youtu.be/fjsJJmPeKuc'
              })]
            }),
            type: "password",
            help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: [__('You need a personal access token from GitHub. Learn how to get one ', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("a", {
                href: "https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-personal-access-token-classic",
                target: "_blank",
                children: __('here', 'simply-static')
              })]
            }),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.github_personal_access_token,
            onChange: token => {
              updateSetting('github_personal_access_token', token);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Repository', 'simply-static'),
            type: "text",
            help: __('Enter a name for your repository (lowercase without spaces or special characters).', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.github_repository,
            onChange: repository => {
              updateSetting('github_repository', repository);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: "warning",
            isDismissible: false,
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("p", {
              children: [__('Ensure to create the repository and add a readme file to it before running an export as shown in the docs ', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("a", {
                href: "https://docs.simplystatic.com/article/33-set-up-the-github-integration/",
                target: "_blank",
                children: __('here', 'simply-static')
              })]
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
            margin: 5
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Folder', 'simply-static'),
            type: "text",
            help: __('Enter a relative path to a folder if you want to push files under it. Example: for github.com/USER/REPOSITORY/folder1, enter folder1', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.github_folder_path,
            onChange: repository => {
              updateSetting('github_folder_path', repository);
            }
          }), githubAccountType === 'organization' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
              status: "warning",
              isDismissible: false,
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
                children: __('You need to create the repository manually within your organization before connecting it.', 'simply-static')
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
              margin: 5
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
            label: __('Visiblity', 'simply-static'),
            value: githubVisibility,
            help: __('Decide if you want to make your repository public or private.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            options: [{
              label: __('Public', 'simply-static'),
              value: 'public'
            }, {
              label: __('Private', 'simply-static'),
              value: 'private'
            }],
            onChange: visibility => {
              setGithubVisibility(visibility);
              updateSetting('github_repository_visibility', visibility);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Branch', 'simply-static'),
            type: settings.github_branch,
            placeholder: "main",
            help: __('Simply Static automatically uses "main" as branch. You may want to modify that for example to gh-pages. for GitHub Pages.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.github_branch,
            onChange: branch => {
              updateSetting('github_branch', branch);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Webhook URL', 'simply-static'),
            type: "url",
            help: __('Enter your Webhook URL here and Simply Static will send a POST request after all files are commited to GitHub.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.github_webhook_url,
            onChange: webhook => {
              updateSetting('github_webhook_url', webhook);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Throttle Requests', 'simply-static'),
            help: __('Enable this option if you are experiencing issues with the GitHub API rate limit.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __nextHasNoMarginBottom: true,
            checked: throttleGitHubRequests,
            onChange: value => {
              setThrottleGitHubRequests(value);
              updateSetting('github_throttle_requests', value);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Batch size', 'simply-static'),
            type: "number",
            help: __('Enter the number of files you want to be processed in a single batch. If current export fails to deploy, lower the number.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: (_settings$github_batc = settings.github_batch_size) !== null && _settings$github_batc !== void 0 ? _settings$github_batc : 100,
            onChange: size => {
              updateSetting('github_batch_size', size);
            }
          })]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      }), deliveryMethod === 'tiiny' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
                children: [__('Tiiny.host', 'simply-static'), " ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                  title: __('How to deploy to Tiiny.host', 'simply-static'),
                  videoUrl: 'https://youtu.be/Y9EDaQkGl1Y'
                })]
              })
            }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                href: "https://simplystatic.com",
                children: [" ", __('Requires Simply Static Pro', 'simply-static')]
              })
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('Deploying to Tiiny.host is the easiest and fastest deployment option available in Simply Static Pro.', 'simply-static')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            disabled: true,
            label: __('E-Mail', 'simply-static'),
            type: "text",
            help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: [__('This field is auto-filled with the e-mail address used for activating Simply Static Pro.', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("b", {
                children: __('An account will be created automatically on your first deployment.', 'simply-static')
              })]
            }),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: options.admin_email
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Subdomain', 'simply-static'),
            type: "text",
            help: __('That\'s the part before your TLD. Your full URL is the combination of the subdomain plus the domain suffix.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.tiiny_subdomain,
            onChange: subdomain => {
              updateSetting('tiiny_subdomain', subdomain);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Domain Suffix', 'simply-static'),
            type: "text",
            help: __('This defaults to tiiny.site. If you have a custom domain configured in Tiiny.host, you can also use  that one.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.tiiny_domain_suffix,
            onChange: suffix => {
              updateSetting('tiiny_domain_suffix', suffix);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Password Protection', 'simply-static'),
            type: "password",
            help: __('Adding a password will activate password protection on your static site. The website is only visible with the password.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.tiiny_password,
            onChange: password => {
              updateSetting('tiiny_password', password);
            }
          })]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      }), deliveryMethod === 'cdn' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
                children: [__('Bunny CDN', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                  title: __('How to deploy to Bunny CDN', 'simply-static'),
                  videoUrl: 'https://youtu.be/FBRg1BI41VY'
                })]
              })
            }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                href: "https://simplystatic.com",
                children: [" ", __('Requires Simply Static Pro', 'simply-static')]
              })
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('Bunny CDN is a fast and reliable CDN provider that you can run your static website on.', 'simply-static')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Bunny CDN API Key', 'simply-static'),
            type: "password",
            help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: [__('Enter your API Key from Bunny CDN. You can find your API-Key as described ', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("a", {
                href: "https://support.bunny.net/hc/en-us/articles/360012168840-Where-do-I-find-my-API-key",
                target: "_blank",
                children: __('here', 'simply-static')
              })]
            }),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.cdn_api_key,
            onChange: api_key => {
              updateSetting('cdn_api_key', api_key);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Storage Host', 'simply-static'),
            type: "text",
            help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: [__('Depending on your location, you have a different storage host. You find out which URL to use ', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("a", {
                href: "https://docs.bunny.net/reference/storage-api#storage-endpoints",
                target: "_blank",
                children: __('here', 'simply-static')
              })]
            }),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.cdn_storage_host,
            onChange: storage_host => {
              updateSetting('cdn_storage_host', storage_host);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Bunny CDN Access Key', 'simply-static'),
            type: "password",
            help: __('Enter your Acess Key from Bunny CDN. You will find it within your storage zone setttings within FTP & API Access -> Password.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.cdn_access_key,
            onChange: access_key => {
              updateSetting('cdn_access_key', access_key);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Pull Zone', 'simply-static'),
            type: "text",
            help: __('A pull zone is the connection of your CDN to the internet. Simply Static will try to find an existing pull zone with the provided name, if there is none it creates a new pull zone.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.cdn_pull_zone,
            onChange: pull_zone => {
              updateSetting('cdn_pull_zone', pull_zone);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Storage Zone', 'simply-static'),
            type: "text",
            help: __('A storage zone contains your static files. Simply Static will try to find an existing storage zone with the provided name, if there is none it creates a new storage zone.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.cdn_storage_zone,
            onChange: storage_zone => {
              updateSetting('cdn_storage_zone', storage_zone);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Subdirectory', 'simply-static'),
            type: "text",
            placeholder: '/subdirectory/',
            help: __('If you want to transfer the files to a specific subdirectory on your storage zone add the name of that directory here.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.cdn_directory,
            onChange: directory => {
              updateSetting('cdn_directory', directory);
            }
          })]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      }), deliveryMethod === 'aws-s3' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
                children: [__('Amazon AWS S3', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                  title: __('How to deploy to Amazon AWS S3', 'simply-static'),
                  videoUrl: 'https://youtu.be/rtn21J86Upc'
                })]
              })
            }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                href: "https://simplystatic.com",
                children: [" ", __('Requires Simply Static Pro', 'simply-static')]
              })
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
            label: __('Authentication Method', 'simply-static'),
            value: awsAuthMethod,
            options: [{
              label: __('AWS IAM Access Key', 'simply-static'),
              value: 'aws-iam-key'
            }, {
              label: __('AWS EC2', 'simply-static'),
              value: 'aws-ec2'
            }],
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            onChange: method => {
              setAwsAuthMethod(method);
              updateSetting('aws_auth_method', method);
            }
          }), awsAuthMethod === 'aws-iam-key' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
              label: __('Access Key ID', 'simply-static'),
              type: "text",
              help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
                children: [__('Enter your Access Key from AWS. Learn how to get one ', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("a", {
                  href: "https://docs.aws.amazon.com/en_en/IAM/latest/UserGuide/id_credentials_access-keys.html",
                  target: "_blank",
                  children: __('here', 'simply-static')
                })]
              }),
              disabled: 'free' === options.plan || !isPro(),
              __next40pxDefaultSize: true,
              __nextHasNoMarginBottom: true,
              value: settings.aws_access_key,
              onChange: access_key => {
                updateSetting('aws_access_key', access_key);
              }
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
              label: __('Secret Access Key', 'simply-static'),
              type: "password",
              help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
                children: [__('Enter your Secret Key from AWS. Learn how to get one ', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("a", {
                  href: "https://docs.aws.amazon.com/en_en/IAM/latest/UserGuide/id_credentials_access-keys.html",
                  target: "_blank",
                  children: __('here', 'simply-static')
                })]
              }),
              disabled: 'free' === options.plan || !isPro(),
              __next40pxDefaultSize: true,
              __nextHasNoMarginBottom: true,
              value: settings.aws_access_secret,
              onChange: secret => {
                updateSetting('aws_access_secret', secret);
              }
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
            label: __('Region', 'simply-static'),
            value: region,
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            options: [{
              label: __('US East (Ohio)', 'simply-static'),
              value: 'us-east-2'
            }, {
              label: __('US East (N. Virginia)', 'simply-static'),
              value: 'us-east-1'
            }, {
              label: __('US West (N. California)', 'simply-static'),
              value: 'us-west-1'
            }, {
              label: __('US West (Oregon)', 'simply-static'),
              value: 'us-west-2'
            }, {
              label: __('Africa (Cape Town)', 'simply-static'),
              value: 'af-south-1'
            }, {
              label: __('Asia Pacific (Hong Kong)', 'simply-static'),
              value: 'ap-east-1'
            }, {
              label: __('Asia Pacific (Hyderabad)', 'simply-static'),
              value: 'ap-south-2'
            }, {
              label: __('Asia Pacific (Jakarta)', 'simply-static'),
              value: 'ap-southeast-3'
            }, {
              label: __('Asia Pacific (Melbourne)', 'simply-static'),
              value: 'ap-southeast-4'
            }, {
              label: __('Asia Pacific (Mumbai)', 'simply-static'),
              value: 'ap-south-1'
            }, {
              label: __('Asia Pacific (Osaka)', 'simply-static'),
              value: 'ap-northeast-3'
            }, {
              label: __('Asia Pacific (Seoul)', 'simply-static'),
              value: 'ap-northeast-2'
            }, {
              label: __('Asia Pacific (Singapore)', 'simply-static'),
              value: 'ap-southeast-1'
            }, {
              label: __('Asia Pacific (Sydney)', 'simply-static'),
              value: 'ap-southeast-2'
            }, {
              label: __('Asia Pacific (Tokyo)', 'simply-static'),
              value: 'ap-northeast-1'
            }, {
              label: __('Canada (Central)', 'simply-static'),
              value: 'ca-central-1'
            }, {
              label: __('Europe (Frankfurt)', 'simply-static'),
              value: 'eu-central-1'
            }, {
              label: __('Europe (Ireland)', 'simply-static'),
              value: 'eu-west-1'
            }, {
              label: __('Europe (London)', 'simply-static'),
              value: 'eu-west-2'
            }, {
              label: __('Europe (Milan)', 'simply-static'),
              value: 'eu-south-1'
            }, {
              label: __('Europe (Paris)', 'simply-static'),
              value: 'eu-west-3'
            }, {
              label: __('Europe (Spain)', 'simply-static'),
              value: 'eu-south-2'
            }, {
              label: __('Europe (Stockholm)', 'simply-static'),
              value: 'eu-north-1'
            }, {
              label: __('Europe (Zurich)', 'simply-static'),
              value: 'eu-central-2'
            }, {
              label: __('Middle East (Bahrain)', 'simply-static'),
              value: 'me-south-1'
            }, {
              label: __('Middle East (UAE)', 'simply-static'),
              value: 'me-central-1'
            }, {
              label: __('South America (São Paulo)', 'simply-static'),
              value: 'sa-east-1'
            }, {
              label: __('AWS GovCloud (US-East)', 'simply-static'),
              value: 'us-gov-east-1'
            }, {
              label: __('AWS GovCloud (US-West)', 'simply-static'),
              value: 'us-gov-west-1'
            }],
            disabled: 'free' === options.plan || !isPro(),
            onChange: region => {
              setRegion(region);
              updateSetting('aws_region', region);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Bucket', 'simply-static'),
            type: "text",
            help: __('Add the name of your bucket here.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.aws_bucket,
            onChange: bucket => {
              updateSetting('aws_bucket', bucket);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Subdirectory', 'simply-static'),
            type: "text",
            help: __('Add an optional subdirectory for your bucket', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.aws_subdirectory,
            onChange: subdirectory => {
              updateSetting('aws_subdirectory', subdirectory);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Cloudfront Distribution ID', 'simply-static'),
            type: "text",
            help: __('We automatically invalidate the cache after each export.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.aws_distribution_id,
            onChange: distribution_id => {
              updateSetting('aws_distribution_id', distribution_id);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Webhook URL', 'simply-static'),
            type: "url",
            help: __('Enter your Webhook URL here and Simply Static will send a POST request after all files are transferred to AWS S3.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.aws_webhook_url,
            onChange: webhook => {
              updateSetting('aws_webhook_url', webhook);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Empty bucket before new export?', 'simply-static'),
            help: emptyBucketBeforeExport ? __('Clear bucket before new export.', 'simply-static') : __('Don\'t clear bucket before new export.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __nextHasNoMarginBottom: true,
            checked: emptyBucketBeforeExport,
            onChange: value => {
              setEmptyBucketBeforeExport(value);
              updateSetting('aws_empty', value);
            }
          })]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      }), deliveryMethod === 'sftp' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
                children: [__('SFTP', 'simply-static'), " ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                  title: __('How to deploy via SFTP', 'simply-static'),
                  videoUrl: 'https://youtu.be/6-QR9wZA3VQ'
                })]
              })
            }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                href: "https://simplystatic.com",
                children: [" ", __('Requires Simply Static Pro', 'simply-static')]
              })
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Host', 'simply-static'),
            type: "text",
            help: __('Enter your SFTP host.', 'simply-static'),
            value: settings.sftp_host,
            disabled: 'free' === options.plan || !isPro(),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            onChange: host => {
              updateSetting('sftp_host', host);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Port', 'simply-static'),
            type: "number",
            disabled: 'free' === options.plan || !isPro(),
            help: __('Enter your SFTP port.', 'simply-static'),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.sftp_port,
            onChange: port => {
              updateSetting('sftp_port', port);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('SFTP username', 'simply-static'),
            help: __('Enter your SFTP username.', 'simply-static'),
            type: "text",
            disabled: 'free' === options.plan || !isPro(),
            placeholder: "username",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.sftp_user,
            onChange: user => {
              updateSetting('sftp_user', user);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('SFTP password', 'simply-static'),
            type: "password",
            disabled: 'free' === options.plan || !isPro(),
            help: __('Enter your SFTP password.', 'simply-static'),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.sftp_pass,
            onChange: pass => {
              updateSetting('sftp_pass', pass);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
            label: __('SFTP private key', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            __nextHasNoMarginBottom: true,
            placeholder: __('OPTIONAL: This is only required if you need to authenticate via a private key to access your SFTP server.', 'simply-static'),
            help: __('Enter your SFTP private key if you want passwordless upload and the server is configured to allow it. You can set it as a constant in wp-config.php by using define(\'SSP_SFTP_KEY\', \'YOUR_KEY\')', 'simply-static'),
            value: settings.sftp_private_key,
            onChange: pass => {
              updateSetting('sftp_private_key', pass);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('SFTP folder', 'simply-static'),
            help: __('Leave empty to upload to the default SFTP folder. Enter a folder path where you want the static files to be uploaded to (example: "uploads" will upload to uploads folder. "uploads/new-folder" will upload files to "new-folder"). ', 'simply-static'),
            type: "text",
            disabled: 'free' === options.plan || !isPro(),
            placeholder: "",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.sftp_folder,
            onChange: folder => {
              updateSetting('sftp_folder', folder);
            }
          })]
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), settingsSaved && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
        type: "slide-in",
        options: {
          origin: 'top'
        },
        children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "success",
          isDismissible: false,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('Settings saved successfully.', 'simply-static')
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("div", {
      className: "save-settings",
      children: ['free' === options.plan ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
        children: [deliveryMethod === 'zip' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          onClick: setSavingSettings,
          variant: "primary",
          children: __('Save Settings', 'simply-static')
        }), deliveryMethod === 'local' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          onClick: setSavingSettings,
          variant: "primary",
          children: __('Save Settings', 'simply-static')
        })]
      }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        onClick: setSavingSettings,
        variant: "primary",
        children: __('Save Settings', 'simply-static')
      }), 'pro' === options.plan && isPro() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        disabled: isRunning || testDisabled || testRunning,
        variant: 'secondary',
        isBusy: isRunning || testRunning,
        onClick: () => {
          setTestRunning(true);
          _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
            path: '/simplystatic/v1/apply-single',
            method: 'POST'
          }).then(resp => {
            if (parseInt(resp.status) === 404) {
              alert(resp.message);
            } else {
              window.location.reload();
            }
          });
        },
        children: [testDisabled && __('Save settings to test', 'simply-static'), !testDisabled && __('Test Deployment', 'simply-static')]
      })]
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DeploymentSettings);

/***/ }),

/***/ "./src/settings/pages/Diagnostics.jsx":
/*!********************************************!*\
  !*** ./src/settings/pages/Diagnostics.jsx ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _components_HelperVideo__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/HelperVideo */ "./src/settings/components/HelperVideo.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);





const {
  __
} = wp.i18n;
function Diagnostics() {
  const {
    configs,
    resetDiagnostics
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  const [isReset, setIsReset] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const runResetDiagnostics = () => {
    resetDiagnostics();
    setIsReset(true);
    setTimeout(function () {
      window.location.reload();
    }, 2000);
  };
  const statusData = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("b", {
          children: [__('Diagnostics', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_3__["default"], {
            title: __('How to use diagnostics', 'simply-static'),
            videoUrl: 'https://youtu.be/X59YMlz6F2s'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('Our diagnostics tool provides detailed insights into your WordPress installation and server configuration and tells you exactly what needs to be optimized to get the most out of Simply Static. Click the button below to get the latest results.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
            onClick: runResetDiagnostics,
            variant: "secondary",
            children: __('Reset Diagnostics', 'simply-static')
          })
        }), isReset ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
          type: "slide-in",
          options: {
            origin: 'top'
          },
          children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: "success",
            isDismissible: false,
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
              children: __('Diagnostics resetted successfully.', 'simply-static')
            })
          })
        }) : '']
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), Object.keys(configs).map(key => {
      const items = configs[key];
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("b", {
              children: key
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("table", {
                style: {
                  width: "100%",
                  tableLayout: "fixed"
                },
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("tbody", {
                  className: "table-data",
                  children: Object.entries(items).map(item => {
                    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("tr", {
                      className: "table-row",
                      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("td", {
                        className: "diagnostics-icon",
                        children: [" ", item[1].test ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
                          className: "icon-yes",
                          icon: "yes"
                        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
                          className: "icon-no",
                          icon: "no"
                        })]
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("td", {
                        className: "diagnostics-test",
                        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("b", {
                          children: item[0]
                        })
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("td", {
                        children: item[1].test
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("td", {
                        className: "diagnostics-result",
                        children: [" ", item[1].test ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
                          children: item[1].description
                        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
                          children: item[1].error
                        })]
                      })]
                    }, item[0]);
                  })
                })
              })
            })
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
          margin: 5
        })]
      }, key);
    })]
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
    className: "inner-settings",
    children: statusData()
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Diagnostics);

/***/ }),

/***/ "./src/settings/pages/FormSettings.jsx":
/*!*********************************************!*\
  !*** ./src/settings/pages/FormSettings.jsx ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/HelperVideo */ "./src/settings/components/HelperVideo.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);






const {
  __
} = wp.i18n;
function FormSettings() {
  const {
    settings,
    updateSetting,
    saveSettings,
    settingsSaved,
    setSettingsSaved,
    isPro
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  const [corsMethod, setCorsMethod] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('allowed_http_origins');
  const [useForms, setUseForms] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [useComments, setUseComments] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [pagesSlugs, setPagesSlugs] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const setSavingSettings = () => {
    saveSettings();
    setSettingsSaved(true);
    setTimeout(function () {
      setSettingsSaved(false);
      if (useForms) {
        localStorage.setItem('ss-initial-page', '/forms');
        window.location.reload();
      }
    }, 2000);
  };
  const getPages = () => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: '/simplystatic/v1/pages-slugs'
    }).then(fetched_pages => {
      let pages = fetched_pages;
      pages.unshift({
        label: __('No page selected', 'simply-static'),
        value: ''
      });
      setPagesSlugs(pages);
    });
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    getPages();
    if (settings.fix_cors) {
      setCorsMethod(settings.fix_cors);
    }
    if (settings.use_forms) {
      setUseForms(settings.use_forms);
    }
    if (settings.use_comments) {
      setUseComments(settings.use_comments);
    }
  }, [settings]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("div", {
    className: "inner-settings",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("b", {
              children: __('Forms', 'simply-static')
            })
          }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: [" ", __('Requires Simply Static Pro', 'simply-static')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Use forms?', 'simply-static'),
          __nextHasNoMarginBottom: true,
          help: useForms ? __('Use Forms on your static website.', 'simply-static') : __('Don\'t use forms on your static website.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          checked: useForms,
          onChange: value => {
            setUseForms(value);
            updateSetting('use_forms', value);
          }
        }), useForms && options.form_connection_url && 'free' !== options.plan && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          href: options.form_connection_url,
          variant: "secondary",
          children: __('Create a form connection', 'simply-static')
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("b", {
              children: __('Comments', 'simply-static')
            })
          }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: [" ", __('Requires Simply Static Pro', 'simply-static')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Use comments?', 'simply-static'),
          __nextHasNoMarginBottom: true,
          help: useComments ? __('Use comments on your static website.', 'simply-static') : __('Don\'t use comments on your static website.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          checked: useComments,
          onChange: value => {
            setUseComments(value);
            updateSetting('use_comments', value);
          }
        }), useComments && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
            label: __('Select a redirect page', 'content-protector'),
            options: pagesSlugs,
            help: __('The post will be regenerated after comment submission, but it might take a while so its good practice to redirect the visitor.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.comment_redirect,
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            onChange: value => {
              updateSetting('comment_redirect', value);
            }
          })
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
              children: [__('CORS', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                title: __('How to deal with CORS', 'simply-static'),
                videoUrl: 'https://youtu.be/fArtvZhkU14'
              })]
            })
          }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: [" ", __('Requires Simply Static Pro', 'simply-static')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
          children: __('When using Forms and Comments in Simply Static Pro you may encounter CORS issues as you make requests from your static website to your original one.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "warning",
          isDismissible: false,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('Due to the variety of server setups out there, you may need to make changes on your server.', 'simply-static')
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
          margin: 5
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('Static URL', 'simply-static'),
          type: "url",
          placeholder: 'https://static-site.com',
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          help: __('Add the URL of your static website to allow CORS from it.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          value: settings.static_url,
          onChange: url => {
            updateSetting('static_url', url);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
          label: __('Select CORS method', 'simply-static'),
          value: corsMethod,
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          help: __('Choose one of the methods to allow CORS for your website.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          options: [{
            label: 'allowed_http_origins',
            value: 'allowed_http_origins'
          }, {
            label: 'wp_headers',
            value: 'wp_headers'
          }],
          onChange: method => {
            setCorsMethod(method);
            updateSetting('fix_cors', method);
          }
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
              children: [__('Embed Dynamic Content (iFrame)', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                title: __('Embed Dynamic Content (iFrame)', 'simply-static'),
                videoUrl: 'https://youtu.be/ZGRaG_Jma7E'
              })]
            })
          }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: [" ", __('Requires Simply Static Pro', 'simply-static')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("p", {
          children: [__('We replace the HTML of the URLs with an iFrame that embeds the content directly from your WordPress website.', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("br", {}), __('This way you can use dynamic elements on your static website without the need of a specific integration.', 'simply-static')]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "warning",
          isDismissible: false,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('This requires your WordPress website to be online all the time.', 'simply-static')
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
          margin: 5
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
          label: __('URLs to embed as an iFrame', 'simply-static'),
          placeholder: options.home + "/my-form-page/",
          __nextHasNoMarginBottom: true,
          help: __('If you want to embed specific pages from your WordPress website into your static website, add the URLs here (one per line).', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          value: settings.iframe_urls,
          onChange: value => {
            updateSetting('iframe_urls', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
          label: __('Custom CSS', 'simply-static'),
          __nextHasNoMarginBottom: true,
          help: __('These styles will only apply to the embedded pages, not your entire website.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          value: settings.iframe_custom_css,
          onChange: value => {
            updateSetting('iframe_custom_css', value);
          }
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), settingsSaved && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
        type: "slide-in",
        options: {
          origin: 'top'
        },
        children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "success",
          isDismissible: false,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('Settings saved successfully.', 'simply-static')
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("div", {
      className: "save-settings",
      children: 'pro' === options.plan && isPro() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        onClick: setSavingSettings,
        variant: "primary",
        children: __('Save Settings', 'simply-static')
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FormSettings);

/***/ }),

/***/ "./src/settings/pages/GeneralSettings.jsx":
/*!************************************************!*\
  !*** ./src/settings/pages/GeneralSettings.jsx ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/HelperVideo */ "./src/settings/components/HelperVideo.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);






const {
  __
} = wp.i18n;
function GeneralSettings() {
  var _settings$custom_404_;
  const {
    settings,
    updateSetting,
    saveSettings,
    settingsSaved,
    setSettingsSaved,
    isPro,
    isRunning,
    setIsRunning
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_3__.SettingsContext);
  const [replaceType, setReplaceType] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)('relative');
  const [useForms, setUseForms] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const [scheme, setScheme] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)('https://');
  const [host, setHost] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)('');
  const [path, setPath] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)('/');
  const [forceURLReplacement, setForceURLReplacement] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const [hasCopied, setHasCopied] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const [generate404, setGenerate404] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const [enableEnhancedCrawl, setEnableEnhancedCrawl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const [addFeeds, setAddFeeds] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const [addRestApi, setAddRestApi] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const [crawlers, setCrawlers] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [selectedCrawlers, setSelectedCrawlers] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [apiError, setApiError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(null);
  const [postTypes, setPostTypes] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [selectedPostTypes, setSelectedPostTypes] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [postTypesApiError, setPostTypesApiError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(null);

  // Plugins/Themes for Enhanced Crawl
  const [plugins, setPlugins] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [selectedPlugins, setSelectedPlugins] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [pluginsApiError, setPluginsApiError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(null);
  const [themes, setThemes] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [selectedThemes, setSelectedThemes] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [themesApiError, setThemesApiError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(null);
  const [pages, setPages] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const setSavingSettings = () => {
    saveSettings();
    setSettingsSaved(true);
    setTimeout(function () {
      setSettingsSaved(false);
    }, 2000);
  };

  // Function to fetch crawlers from API
  const fetchCrawlers = () => {
    // Reset API error
    setApiError(null);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/crawlers',
      // Use raw: true to get the raw response
      parse: true
    }).then(response => {
      // Check if response is a string (JSON) and try to parse it
      if (typeof response === 'string') {
        try {
          response = JSON.parse(response);
        } catch (e) {
          setApiError('Error parsing API response: ' + e.message);
          return;
        }
      }
      if (response && response.data && response.data.length > 0) {
        // Only show crawlers that can currently run (dependencies satisfied)
        const crawlersData = response.data.filter(crawler => crawler.can_run);
        setCrawlers(crawlersData);

        // If no crawlers are selected or settings.crawlers is not an array, select defaults by active flag
        // This ensures that if active_crawlers is empty (like when it's enabled for the first time),
        // only crawlers active by default are added by default
        if (!settings.crawlers || !Array.isArray(settings.crawlers) || settings.crawlers.length === 0) {
          const defaultCrawlerIds = crawlersData.filter(crawler => crawler.active).map(crawler => crawler.id);
          setSelectedCrawlers(defaultCrawlerIds);
          updateSetting('crawlers', defaultCrawlerIds);
        } else if (Array.isArray(settings.crawlers)) {
          // Ensure all selected crawlers exist in the allowed crawlers list
          const validCrawlerIds = settings.crawlers.filter(id => crawlersData.some(crawler => crawler.id === id));

          // If no valid crawlers are selected, select all allowed by default
          if (validCrawlerIds.length === 0) {
            const allCrawlerIds = crawlersData.map(crawler => crawler.id);
            setSelectedCrawlers(allCrawlerIds);
            updateSetting('crawlers', allCrawlerIds);
          } else {
            setSelectedCrawlers(validCrawlerIds);
          }
        }
      } else {
        setApiError('Invalid API response structure or empty crawlers array');
      }
    }).catch(error => {
      setApiError('Error fetching crawlers: ' + (error.message || 'Unknown error'));
    });
  };

  // Function to fetch post types from API
  const fetchPostTypes = () => {
    // Reset API error
    setPostTypesApiError(null);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/post-types',
      parse: true
    }).then(response => {
      // Check if response is a string (JSON) and try to parse it
      if (typeof response === 'string') {
        try {
          response = JSON.parse(response);
        } catch (e) {
          setPostTypesApiError('Error parsing API response: ' + e.message);
          return;
        }
      }
      if (response && response.data && response.data.length > 0) {
        setPostTypes(response.data);

        // If settings.post_types is not an array, initialize it as an empty array
        if (!settings.post_types || !Array.isArray(settings.post_types)) {
          const allPostTypeIds = response.data.map(postType => postType.name);
          setSelectedPostTypes(allPostTypeIds);
          updateSetting('post_types', allPostTypeIds);
        } else if (Array.isArray(settings.post_types)) {
          // Ensure all selected post types exist in the post types list
          const validPostTypeIds = settings.post_types.filter(name => response.data.some(postType => postType.name === name));

          // If no valid post types are selected, select all by default
          if (validPostTypeIds.length === 0) {
            const allPostTypeIds = response.data.map(postType => postType.name);
            setSelectedPostTypes(allPostTypeIds);
            updateSetting('post_types', allPostTypeIds);
          } else {
            setSelectedPostTypes(validPostTypeIds);
          }
        }
      } else {
        setPostTypesApiError('Invalid API response structure or empty post types array');
      }
    }).catch(error => {
      setPostTypesApiError('Error fetching post types: ' + (error.message || 'Unknown error'));
    });
  };

  // Fetch plugins
  const fetchPlugins = () => {
    setPluginsApiError(null);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/active-plugins',
      parse: true
    }).then(response => {
      if (typeof response === 'string') {
        response = JSON.parse(response);
      }
      const list = Array.isArray(response?.data) ? response.data : [];
      setPlugins(list);
      // Initialize selection default to all
      const saved = Array.isArray(settings.plugins_to_include) ? settings.plugins_to_include : [];
      const allSlugs = list.map(p => p.slug);
      const valid = saved.filter(slug => allSlugs.includes(slug));
      const initial = valid.length > 0 ? valid : allSlugs;
      setSelectedPlugins(initial);
      updateSetting('plugins_to_include', initial);
    }).catch(error => setPluginsApiError(error?.message || 'Unknown error'));
  };

  // Fetch themes
  const fetchThemes = () => {
    setThemesApiError(null);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/active-themes',
      parse: true
    }).then(response => {
      if (typeof response === 'string') {
        response = JSON.parse(response);
      }
      const list = Array.isArray(response?.data) ? response.data : [];
      setThemes(list);
      const saved = Array.isArray(settings.themes_to_include) ? settings.themes_to_include : [];
      const allSlugs = list.map(t => t.slug);
      const valid = saved.filter(slug => allSlugs.includes(slug));
      const initial = valid.length > 0 ? valid : allSlugs;
      setSelectedThemes(initial);
      updateSetting('themes_to_include', initial);
    }).catch(error => setThemesApiError(error?.message || 'Unknown error'));
  };

  // Fetch crawlers and post types when component mounts
  // We intentionally use an empty dependency array to ensure this only runs once
  // when the component mounts, not on every settings change
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    fetchCrawlers();
    fetchPostTypes();
    fetchPlugins();
    fetchThemes();
    // Fetch pages for optional 404 selection
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/simplystatic/v1/pages'
    }).then(fetched_pages => {
      let pages = fetched_pages || [];
      // Prepend default option
      pages.unshift({
        label: __('No page selected', 'simply-static'),
        value: 0
      });
      setPages(pages);
    }).catch(() => {
      setPages([]);
    });
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    if (settings.destination_url_type) {
      setReplaceType(settings.destination_url_type);
    }
    if (settings.destination_scheme) {
      setScheme(settings.destination_scheme);
    }
    if (settings.destination_host) {
      setHost(settings.destination_host);
    }
    if (settings.relative_path) {
      setPath(settings.relative_path);
    }
    if (settings.use_forms || settings.use_comments) {
      setUseForms(true);
    }
    if (settings.force_replace_url) {
      setForceURLReplacement(settings.force_replace_url);
    }
    if (settings.generate_404) {
      setGenerate404(settings.generate_404);
    }
    if (settings.add_feeds) {
      setAddFeeds(settings.add_feeds);
    }
    if (settings.add_rest_api) {
      setAddRestApi(settings.add_rest_api);
    }
    if (settings.smart_crawl) {
      setEnableEnhancedCrawl(settings.smart_crawl);
    }
    if (settings.crawlers) {
      setSelectedCrawlers(settings.crawlers);
    }
    if (settings.post_types !== undefined) {
      setSelectedPostTypes(Array.isArray(settings.post_types) ? settings.post_types : []);
    }
  }, [settings]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("div", {
    className: "inner-settings",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
          children: [__('Replacing URLs', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
            title: __('How to replace URLs', 'simply-static'),
            videoUrl: 'https://youtu.be/cb8jAMJlfGI'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
          children: __('When exporting your static site, any links to your WordPress site will be replaced by one of the following: absolute URLs, relative URLs, or URLs contructed for offline use.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
          label: __('Replacing URLs', 'simply-static'),
          value: replaceType,
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          options: [{
            label: __('Absolute URLs', 'simply-static'),
            value: 'absolute'
          }, {
            label: __('Relative Path', 'simply-static'),
            value: 'relative'
          }, {
            label: __('Offline Usage', 'simply-static'),
            value: 'offline'
          }],
          onChange: type => {
            setReplaceType(type);
            updateSetting('destination_url_type', type);
          }
        }), replaceType === 'absolute' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              style: {
                minWidth: "15%",
                marginTop: "15px"
              },
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
                label: __('Scheme', 'simply-static'),
                value: scheme,
                __next40pxDefaultSize: true,
                __nextHasNoMarginBottom: true,
                options: [{
                  label: 'https://',
                  value: 'https://'
                }, {
                  label: 'http://',
                  value: 'http://'
                }, {
                  label: '//',
                  value: '//'
                }],
                onChange: scheme => {
                  setScheme(scheme);
                  updateSetting('destination_scheme', scheme);
                }
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              style: {
                minWidth: "85%"
              },
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
                label: __('Host', 'simply-static'),
                type: "text",
                placeholder: "example.com",
                __next40pxDefaultSize: true,
                __nextHasNoMarginBottom: true,
                value: host,
                onChange: host => {
                  setHost(host);
                  updateSetting('destination_host', host);
                }
              })
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('Convert all URLs for your WordPress site to absolute URLs at the domain specified above.', 'simply-static')
          })]
        }), replaceType === 'relative' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Path', 'simply-static'),
            type: "text",
            placeholder: "/",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: path,
            onChange: path => {
              setPath(path);
              updateSetting('relative_path', path);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("p", {
            children: [__('Convert all URLs for your WordPress site to relative URLs that will work at any domain.', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("br", {}), __('Optionally specify a path above if you intend to place the files in a subdirectory.', 'simply-static')]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: "warning",
            isDismissible: false,
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
              children: [__('Example', 'simply-static'), ": "]
            }), __('enter /path above if you wanted to serve your files at www.example.com/path/', 'simply-static')]
          })]
        }), replaceType === 'offline' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
          children: __('Convert all URLs for your WordPress site so that you can browse the site locally on your own computer without hosting it on a web server.', 'simply-static')
        }), !useForms && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Force URL replacements', 'simply-static'),
          __nextHasNoMarginBottom: true,
          help: forceURLReplacement ? __('Replace all occurrences of the WordPress URL with the static URL (includes inline CSS and JS).', 'simply-static') : __('Replace only occurrences of the WordPress URL that match our tag list.', 'simply-static'),
          checked: forceURLReplacement,
          onChange: value => {
            setForceURLReplacement(value);
            updateSetting('force_replace_url', value);
          }
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
          children: [__('Enhanced Crawl', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
            title: __('How Enhanced Crawl improves your static exports', 'simply-static'),
            videoUrl: 'https://youtu.be/QfKxeQ1w7tU'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
          children: __('Enhanced Crawl uses native WordPress functions to find all pages and files when running a static export.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          __nextHasNoMarginBottom: true,
          label: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: __('Enable Enhanced Crawl', 'simply-static')
          }),
          help: enableEnhancedCrawl ? __('Find pages and files via Enhanced Crawl.', 'simply-static') : __('Don\'t find pages and files via Enhanced Crawl.', 'simply-static'),
          checked: enableEnhancedCrawl,
          onChange: value => {
            setEnableEnhancedCrawl(value);
            updateSetting('smart_crawl', value);
          }
        }), enableEnhancedCrawl && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
            margin: 2
          }), apiError && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
              status: "error",
              isDismissible: false,
              children: [__('Error loading crawlers: ', 'simply-static'), " ", apiError]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
              margin: 2
            })]
          }), crawlers.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FormTokenField, {
              label: __('Active Crawlers', 'simply-static'),
              __next40pxDefaultSize: true,
              __nextHasNoMarginBottom: true,
              value: selectedCrawlers.map(id => {
                const crawler = crawlers.find(c => c.id === id);
                return crawler ? crawler.name : id;
              }),
              suggestions: crawlers.map(crawler => crawler.name),
              onChange: value => {
                // Convert names to IDs for storage, and only allow known/available crawlers
                const selectedIds = value.map(name => {
                  // First try to find an exact match
                  let crawler = crawlers.find(c => c.name === name);

                  // If no exact match, try case-insensitive match
                  if (!crawler) {
                    crawler = crawlers.find(c => c.name.toLowerCase() === (name || '').toLowerCase());
                  }

                  // If still no match, check if it's already an ID
                  if (!crawler) {
                    crawler = crawlers.find(c => c.id === name);
                  }
                  return crawler ? crawler.id : null;
                }).filter(id => !!id && crawlers.some(c => c.id === id));
                setSelectedCrawlers(selectedIds);
                updateSetting('crawlers', selectedIds);
              },
              help: __('Select which crawlers to activate. If none selected, all crawlers will be active by default.', 'simply-static'),
              tokenizeOnSpace: false,
              __experimentalExpandOnFocus: true,
              __experimentalShowHowTo: false,
              maxSuggestions: 100,
              className: "horizontal-token-field"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
              margin: 2
            }), selectedCrawlers.includes('post_type') && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
                margin: 2
              }), postTypesApiError && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
                  status: "error",
                  isDismissible: false,
                  children: [__('Error loading post types: ', 'simply-static'), " ", postTypesApiError]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
                  margin: 2
                })]
              }), postTypes.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FormTokenField, {
                  label: __('Post Types to Include', 'simply-static'),
                  __next40pxDefaultSize: true,
                  __nextHasNoMarginBottom: true,
                  value: Array.isArray(selectedPostTypes) ? selectedPostTypes.map(name => {
                    const postType = postTypes.find(pt => pt.name === name);
                    return postType ? postType.label : name;
                  }) : [],
                  suggestions: postTypes.map(postType => postType.label),
                  onChange: value => {
                    // Convert labels to names for storage
                    const selectedNames = value.map(label => {
                      // First try to find an exact match
                      let postType = postTypes.find(pt => pt.label === label);

                      // If no exact match, try case-insensitive match
                      if (!postType) {
                        postType = postTypes.find(pt => pt.label.toLowerCase() === label.toLowerCase());
                      }

                      // If still no match, check if it's already a name
                      if (!postType) {
                        postType = postTypes.find(pt => pt.name === label);
                      }
                      return postType ? postType.name : label;
                    });
                    setSelectedPostTypes(selectedNames);
                    updateSetting('post_types', selectedNames);
                  },
                  help: __('Select which post types to include in the static export. If you remove all selections, all post types will be included by default.', 'simply-static'),
                  tokenizeOnSpace: false,
                  __experimentalExpandOnFocus: true,
                  __experimentalShowHowTo: false,
                  maxSuggestions: 100,
                  className: "horizontal-token-field"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
                  margin: 2
                })]
              }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
                children: __('Loading post types...', 'simply-static')
              })]
            }), selectedCrawlers.includes('plugin_assets') && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: [pluginsApiError && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
                  status: "error",
                  isDismissible: false,
                  children: [__('Error loading plugins: ', 'simply-static'), " ", pluginsApiError]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
                  margin: 2
                })]
              }), plugins.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FormTokenField, {
                  label: __('Plugins to Include', 'simply-static'),
                  __next40pxDefaultSize: true,
                  __nextHasNoMarginBottom: true,
                  disabled: !isPro(),
                  value: Array.isArray(selectedPlugins) ? selectedPlugins.map(slug => {
                    const pl = plugins.find(p => p.slug === slug);
                    return pl ? pl.label : slug;
                  }) : [],
                  suggestions: plugins.map(p => p.label),
                  onChange: value => {
                    const selected = value.map(label => {
                      let pl = plugins.find(p => p.label === label) || plugins.find(p => p.label.toLowerCase() === String(label).toLowerCase()) || plugins.find(p => p.slug === label);
                      return pl ? pl.slug : label;
                    });
                    setSelectedPlugins(selected);
                    updateSetting('plugins_to_include', selected);
                  },
                  help: __('Select which active plugins to include. All active plugins are included by default; remove tokens to exclude them.', 'simply-static'),
                  tokenizeOnSpace: false,
                  __experimentalExpandOnFocus: true,
                  __experimentalShowHowTo: false,
                  maxSuggestions: 100,
                  className: "horizontal-token-field"
                }), !isPro() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                  style: {
                    marginTop: "5px"
                  },
                  href: "https://simplystatic.com",
                  children: [" ", __('Requires Simply Static Pro', 'simply-static')]
                })]
              }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
                children: __('Loading plugins...', 'simply-static')
              })]
            }), selectedCrawlers.includes('theme_assets') && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: [themesApiError && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
                  status: "error",
                  isDismissible: false,
                  children: [__('Error loading themes: ', 'simply-static'), " ", themesApiError]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
                  margin: 2
                })]
              }), themes.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
                children: [isPro() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
                  margin: "5px"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FormTokenField, {
                  label: __('Themes to Include', 'simply-static'),
                  __next40pxDefaultSize: true,
                  __nextHasNoMarginBottom: true,
                  disabled: !isPro(),
                  value: Array.isArray(selectedThemes) ? selectedThemes.map(slug => {
                    const th = themes.find(t => t.slug === slug);
                    return th ? th.label : slug;
                  }) : [],
                  suggestions: themes.map(t => t.label),
                  onChange: value => {
                    const selected = value.map(label => {
                      let th = themes.find(t => t.label === label) || themes.find(t => t.label.toLowerCase() === String(label).toLowerCase()) || themes.find(t => t.slug === label);
                      return th ? th.slug : label;
                    });
                    setSelectedThemes(selected);
                    updateSetting('themes_to_include', selected);
                  },
                  help: __('Select which theme(s) to include. The active theme and parent (if any) are included by default; remove tokens to exclude them.', 'simply-static'),
                  tokenizeOnSpace: false,
                  __experimentalExpandOnFocus: true,
                  __experimentalShowHowTo: false,
                  maxSuggestions: 100,
                  className: "horizontal-token-field"
                }), !isPro() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                  style: {
                    marginTop: "5px"
                  },
                  href: "https://simplystatic.com",
                  children: [" ", __('Requires Simply Static Pro', 'simply-static')]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
                  margin: 2
                })]
              }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
                children: __('Loading themes...', 'simply-static')
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("div", {
              className: "crawler-descriptions",
              children: crawlers.map(crawler => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("div", {
                className: "crawler-description",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
                    className: "crawler-name",
                    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("strong", {
                      children: [crawler.name, ":"]
                    })
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
                    children: crawler.description
                  })]
                })
              }, crawler.id))
            })]
          }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('Loading crawlers...', 'simply-static')
          })]
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
          children: [__('404', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
            title: __('How to manage 404 pages?', 'simply-static'),
            videoUrl: 'https://youtu.be/dnRtuQrXG-k'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          __nextHasNoMarginBottom: true,
          label: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: __('Generate 404 Page?', 'simply-static')
          }),
          help: generate404 ? __('Generate a 404 page based on your theme template.', 'simply-static') : __('Don\'t generate a 404 page.', 'simply-static'),
          checked: generate404,
          onChange: value => {
            setGenerate404(value);
            updateSetting('generate_404', value);
          }
        }), generate404 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
            label: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
              children: !(isPro && typeof isPro === 'function' ? isPro() : false) ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("span", {
                  style: {
                    position: "relative",
                    bottom: "-15px"
                  },
                  children: __('Custom 404 page (optional)', 'simply-static')
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                  href: "https://simplystatic.com",
                  children: __('Requires Simply Static Pro', 'simply-static')
                })]
              }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
                children: __('Custom 404 page (optional)', 'simply-static')
              })
            }),
            value: (_settings$custom_404_ = settings.custom_404_page) !== null && _settings$custom_404_ !== void 0 ? _settings$custom_404_ : 0,
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            options: pages,
            disabled: !(isPro && typeof isPro === 'function' ? isPro() : false),
            onChange: pageId => {
              updateSetting('custom_404_page', pageId);
            },
            help: __('If selected, Simply Static will use the content of this page for the 404 page instead of the theme default.', 'simply-static')
          }), (isPro && typeof isPro === 'function' ? isPro() : false) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
            variant: "secondary",
            disabled: isRunning,
            isBusy: isRunning,
            onClick: () => {
              // Trigger 404-only export via REST
              if (isRunning) {
                return;
              }
              setIsRunning(true);
              _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
                path: '/simplystatic/v1/export-404',
                method: 'POST'
              }).then(resp => {
                try {
                  if (resp && resp.success === false && resp.message) {
                    alert(resp.message);
                  } else {
                    // Reload to reflect new status/progress
                    window.location.reload();
                  }
                } catch (e) {
                  // Fallback reload
                  window.location.reload();
                }
              }).catch(() => {
                // Reset running flag on error
                setIsRunning(false);
                alert(__('Failed to start 404 export.', 'simply-static'));
              });
            },
            children: __('Export 404 Page', 'simply-static')
          })]
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
          children: [__('Include', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
            title: __('Include & Exclude files and pages', 'simply-static'),
            videoUrl: 'https://youtu.be/voAHfwVMLi8'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
          label: __('Additional URLs', 'simply-static'),
          placeholder: options.home + "/hidden-page/\n/\\/blog\\/.*$/i ",
          __nextHasNoMarginBottom: true,
          help: __('You can enter full URLs or regex (wrap with / /). Examples: https://example.com/private/ or /\\/blog\\/.*$/i (one per line).', 'simply-static'),
          value: settings.additional_urls,
          onChange: value => {
            updateSetting('additional_urls', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
          label: __('Additional Files and Directories', 'simply-static'),
          placeholder: options.home_path + "additional-directory/\n" + options.home_path + "additional-file.html\n/\\.pdf$/i",
          __nextHasNoMarginBottom: true,
          help: __('Enter absolute paths or regex (wrap with / /). Examples: /\\.pdf$/i or /wp-content\\/uploads\\/reports\\/.*/ (one per line).', 'simply-static'),
          value: settings.additional_files,
          onChange: value => {
            updateSetting('additional_files', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
          variant: "secondary",
          onClick: () => {
            try {
              navigator.clipboard.writeText(options.home_path);
              setHasCopied(true);
              setTimeout(() => setHasCopied(false), 1500);
            } catch (e) {
              console.error('Clipboard copy failed', e);
            }
          },
          children: hasCopied ? __('Copied home path', 'simply-static') : __('Copy home path', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          __nextHasNoMarginBottom: true,
          label: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: __('Include RSS Feeds?', 'simply-static')
          }),
          help: addFeeds ? __('Include feed URLs of all your posts.', 'simply-static') : __('Don\'t include feed URLs for all your posts.', 'simply-static'),
          checked: addFeeds,
          onChange: value => {
            setAddFeeds(value);
            updateSetting('add_feeds', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          __nextHasNoMarginBottom: true,
          label: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: __('Include Rest API?', 'simply-static')
          }),
          help: addRestApi ? __('Include all Rest API endpoints as JSON files.', 'simply-static') : __('Don\'t include Rest API endpoints as JSON files.', 'simply-static'),
          checked: addRestApi,
          onChange: value => {
            setAddRestApi(value);
            updateSetting('add_rest_api', value);
          }
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
          children: [__('Exclude', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
            title: __('Include & Exclude files and pages', 'simply-static'),
            videoUrl: 'https://youtu.be/voAHfwVMLi8'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
          label: __('Urls to exclude', 'simply-static'),
          placeholder: "/some-directory/\n.jpg\n/\\.(pdf|zip)$/i\n/\\/private\\/.*/",
          __nextHasNoMarginBottom: true,
          help: __('You can enter substrings or regex (wrap with / /). Examples: .jpg or /\\.(pdf|zip)$/i or /\/private\/.*/ (one per line).', 'simply-static'),
          value: settings.urls_to_exclude,
          onChange: value => {
            updateSetting('urls_to_exclude', value);
          }
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), settingsSaved && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
        type: "slide-in",
        options: {
          origin: 'top'
        },
        children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "success",
          isDismissible: false,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('Settings saved successfully.', 'simply-static')
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("div", {
      className: "save-settings",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        onClick: setSavingSettings,
        variant: "primary",
        children: __('Save Settings', 'simply-static')
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (GeneralSettings);

/***/ }),

/***/ "./src/settings/pages/Generate.jsx":
/*!*****************************************!*\
  !*** ./src/settings/pages/Generate.jsx ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _components_ActivityLog__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/ActivityLog */ "./src/settings/components/ActivityLog.jsx");
/* harmony import */ var _components_ExportLog__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/ExportLog */ "./src/settings/components/ExportLog.jsx");
/* harmony import */ var _components_LogButtons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/LogButtons */ "./src/settings/components/LogButtons.jsx");
/* harmony import */ var _components_Sites__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../components/Sites */ "./src/settings/components/Sites.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__);








const {
  __
} = wp.i18n;
function Generate() {
  const {
    settings,
    blogId,
    setBlogId
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
    className: "inner-settings",
    children: [!options.is_network && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_components_ActivityLog__WEBPACK_IMPORTED_MODULE_3__["default"], {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
      align: "top",
      children: [options.is_network && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
        isBlock: true,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_components_Sites__WEBPACK_IMPORTED_MODULE_6__["default"], {})
          })
        })
      }), settings.debugging_mode && options.log_file && !options.is_network && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
        isBlock: true,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("b", {
              children: __('Debugging', 'simply-static')
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_components_LogButtons__WEBPACK_IMPORTED_MODULE_5__["default"], {})
          })]
        })
      })]
    }), !options.is_network && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("b", {
            children: __('Export Log', 'simply-static')
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_components_ExportLog__WEBPACK_IMPORTED_MODULE_4__["default"], {})
        })]
      })]
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Generate);

/***/ }),

/***/ "./src/settings/pages/IntegrationsSettings.jsx":
/*!*****************************************************!*\
  !*** ./src/settings/pages/IntegrationsSettings.jsx ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _components_Integration__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/Integration */ "./src/settings/components/Integration.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);





const {
  __
} = wp.i18n;
function IntegrationsSettings() {
  const {
    settings,
    updateSetting,
    saveSettings,
    settingsSaved,
    setSettingsSaved,
    maybeQueueIntegration,
    maybeUnqueueIntegration
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  const setSavingSettings = () => {
    saveSettings();
    setSettingsSaved(true);
    setTimeout(function () {
      setSettingsSaved(false);
    }, 2000);
  };
  const saveIntegration = integration => {
    let integrations = settings.integrations;
    if (false === integrations) {
      integrations = [];
    }
    if (integrations.indexOf(integration) >= 0) {
      return;
    }
    integrations.push(integration);
    updateSetting('integrations', integrations);
    maybeQueueIntegration(integration);
  };
  const removeIntegration = integration => {
    let integrations = settings.integrations;
    if (false === integrations) {
      integrations = [];
    }
    const index = integrations.indexOf(integration);
    if (index < 0) {
      return;
    }
    integrations.splice(index, 1);
    updateSetting('integrations', integrations);
    maybeUnqueueIntegration(integration);
  };
  const toggleIntegration = (integration, value) => {
    if (value) {
      saveIntegration(integration);
    } else {
      removeIntegration(integration);
    }
  };
  const canRunIntegrations = Object.keys(options.integrations).filter(item => {
    return options.integrations[item].can_run && !options.integrations[item].always_active;
  });
  const canNotRunIntegrations = Object.keys(options.integrations).filter(item => {
    return !options.integrations[item].can_run && !options.integrations[item].always_active;
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
    className: "inner-settings",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("b", {
          children: __('Integrations', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: __('Control Integrations that will be active during the export of the static site.', 'simply-static')
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), canRunIntegrations.map(item => {
      const integration = options.integrations[item];
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_components_Integration__WEBPACK_IMPORTED_MODULE_3__["default"], {
        integration: integration,
        settings: settings,
        toggleIntegration: toggleIntegration
      }, integration.id || item);
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), canNotRunIntegrations.map(item => {
      const integration = options.integrations[item];
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_components_Integration__WEBPACK_IMPORTED_MODULE_3__["default"], {
        integration: integration,
        settings: settings,
        toggleIntegration: toggleIntegration
      }, integration.id || item);
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), settingsSaved && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
        type: "slide-in",
        options: {
          origin: 'top'
        },
        children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "success",
          isDismissible: false,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
            children: __('Settings saved successfully.', 'simply-static')
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
      className: "save-settings",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        onClick: setSavingSettings,
        variant: "primary",
        children: __('Save Settings', 'simply-static')
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (IntegrationsSettings);

/***/ }),

/***/ "./src/settings/pages/Optimize.jsx":
/*!*****************************************!*\
  !*** ./src/settings/pages/Optimize.jsx ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/HelperVideo */ "./src/settings/components/HelperVideo.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);






const {
  __
} = wp.i18n;
function Optimize() {
  const {
    settings,
    updateSetting,
    saveSettings,
    settingsSaved,
    setSettingsSaved,
    isPro
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  const [minifyFiles, setMinifyFiles] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [minifyHtml, setMinifyHtml] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [minifyCss, setMinifyCss] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [minifyInlineCss, setMinifyInlineCss] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [minifyJavascript, setMinifyJavascript] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [minifyInlineJavascript, setMinifyInlineJavascript] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [wpContentDirectory, setWpContentDirectory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('wp-content');
  const [wpIncludesDirectory, setWpIncludesDirectory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('wp-includes');
  const [wpUploadsDirectory, setWpUploadsDirectory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('wp-content/uploads');
  const [wpPluginsDirectory, setWpPluginsDirectory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('wp-content/plugins');
  const [wpThemesDirectory, setWpThemesDirectory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('wp-content/themes');
  const [themeStyleName, setThemeStyleName] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('style');
  const [authorUrl, setAuthorUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('author');
  const [hideRESTAPI, setHideRESTAPI] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [hideStyleId, setHideStyleId] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [hideComments, setHideComments] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [hideVersion, setHideVersion] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [hidePrefetch, setHidePrefetch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [hideGenerator, setHideGenerator] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [hideRSD, setHideRSD] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [hideEmojis, setHideEmojis] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [disableXMLRPC, setDisableXMLRPC] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [disableEmbed, setDisableEmbed] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [disableDbDebug, setDisableDbDebug] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [disableWLW, setDisableWLW] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [disableDirectory, setDisableDirectory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [shortPixelResetting, setShortPixelResetting] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const setSavingSettings = () => {
    saveSettings();
    setSettingsSaved(true);
    setTimeout(function () {
      setSettingsSaved(false);
    }, 2000);
  };
  const restoreBackups = () => {
    setShortPixelResetting(true);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_3___default()({
      path: '/simplystatic/v1/shortpixel-restore',
      method: 'POST'
    }).then(resp => {
      const json = JSON.parse(resp);
      setShortPixelResetting(false);
      alert(json.message);
    }).catch(error => {
      setShortPixelResetting(false);
      alert(error.message);
    });
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (settings.use_minify) {
      setMinifyFiles(settings.use_minify);
    }
    if (settings.minify_html) {
      setMinifyHtml(settings.minify_html);
    }
    if (settings.minify_css) {
      setMinifyCss(settings.minify_css);
    }
    if (settings.minify_inline_css) {
      setMinifyInlineCss(settings.minify_inline_css);
    }
    if (settings.minify_js) {
      setMinifyJavascript(settings.minify_js);
    }
    if (settings.minify_inline_js) {
      setMinifyInlineJavascript(settings.minify_inline_js);
    }
    if (settings.wp_content_directory) {
      setWpContentDirectory(settings.wp_content_directory);
    }
    if (settings.wp_includes_directory) {
      setWpIncludesDirectory(settings.wp_includes_directory);
    }
    if (settings.wp_uploads_directory) {
      setWpUploadsDirectory(settings.wp_uploads_directory);
    }
    if (settings.wp_plugins_directory) {
      setWpPluginsDirectory(settings.wp_plugins_directory);
    }
    if (settings.wp_themes_directory) {
      setWpThemesDirectory(settings.wp_themes_directory);
    }
    if (settings.theme_style_name) {
      setThemeStyleName(settings.theme_style_name);
    }
    if (settings.author_url) {
      setAuthorUrl(settings.author_url);
    }
    if (settings.hide_comments) {
      setHideComments(settings.hide_comments);
    }
    if (settings.hide_version) {
      setHideVersion(settings.hide_version);
    }
    if (settings.hide_generator) {
      setHideGenerator(settings.hide_generator);
    }
    if (settings.hide_prefetch) {
      setHidePrefetch(settings.hide_prefetch);
    }
    if (settings.hide_rsd) {
      setHideRSD(settings.hide_rsd);
    }
    if (settings.hide_emotes) {
      setHideEmojis(settings.hide_emotes);
    }
    if (settings.disable_xmlrpc) {
      setDisableXMLRPC(settings.disable_xmlrpc);
    }
    if (settings.disable_embed) {
      setDisableEmbed(settings.disable_embed);
    }
    if (settings.disable_db_debug) {
      setDisableDbDebug(settings.disable_db_debug);
    }
    if (settings.disable_wlw_manifest) {
      setDisableWLW(settings.disable_wlw_manifest);
    }
    if (settings.disable_directory_browsing) {
      setDisableDirectory(settings.disable_directory_browsing);
    }
  }, [settings]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("div", {
    className: "inner-settings",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
              children: [__('Minify', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                title: __('How to minify HTML, CSS and JavaScript?', 'simply-static'),
                videoUrl: 'https://youtu.be/52IKv5ai-i4'
              })]
            })
          }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: [" ", __('Requires Simply Static Pro', 'simply-static')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Minify Files?', 'simply-static'),
          __nextHasNoMarginBottom: true,
          help: minifyFiles ? __('Enable minify files on your static website.', 'simply-static') : __('Don\'t enable minify files on your static website.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          checked: minifyFiles,
          onChange: value => {
            setMinifyFiles(value);
            updateSetting('use_minify', value);
          }
        }), minifyFiles && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Minify HTML', 'simply-static'),
            __nextHasNoMarginBottom: true,
            help: minifyHtml ? __('Minify HTML files.', 'simply-static') : __('Don\'t minify HTML files.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            checked: minifyHtml,
            onChange: value => {
              setMinifyHtml(value);
              updateSetting('minify_html', value);
            }
          }), minifyHtml && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Leave quotes inside HTML attributes', 'simply-static'),
            __nextHasNoMarginBottom: true,
            help: __('If there are issues with comments or JavaScript when minifying HTML, toggle this ON.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            checked: !!settings.minify_html_leave_quotes,
            onChange: value => {
              updateSetting('minify_html_leave_quotes', value);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Minify CSS', 'simply-static'),
            __nextHasNoMarginBottom: true,
            help: minifyCss ? __('Minify CSS files.', 'simply-static') : __('Don\'t minify CSS files.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            checked: minifyCss,
            onChange: value => {
              setMinifyCss(value);
              updateSetting('minify_css', value);
            }
          }), minifyCss && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
              label: __('Exclude Stylesheet URLs', 'simply-static'),
              __nextHasNoMarginBottom: true,
              help: __('Exclude URLs from minification (one per line).', 'simply-static'),
              disabled: 'free' === options.plan || !isPro(),
              value: settings.minify_css_exclude,
              onChange: excludes => {
                updateSetting('minify_css_exclude', excludes);
              }
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
              label: __('Minify Inline CSS', 'simply-static'),
              __nextHasNoMarginBottom: true,
              help: minifyInlineCss ? __('Minify Inline CSS.', 'simply-static') : __('Don\'t minify Inline CSS.', 'simply-static'),
              disabled: 'free' === options.plan || !isPro(),
              checked: minifyInlineCss,
              onChange: value => {
                setMinifyInlineCss(value);
                updateSetting('minify_inline_css', value);
              }
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Minify JavaScript', 'simply-static'),
            __nextHasNoMarginBottom: true,
            help: minifyJavascript ? __('Minify JavaScript files.', 'simply-static') : __('Don\'t minify JavaScript files.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            checked: minifyJavascript,
            onChange: value => {
              setMinifyJavascript(value);
              updateSetting('minify_js', value);
            }
          }), minifyJavascript && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
              label: __('Exclude JavaScript URLs', 'simply-static'),
              __nextHasNoMarginBottom: true,
              help: __('Exclude URLs from minification (one per line).', 'simply-static'),
              disabled: 'free' === options.plan || !isPro(),
              value: settings.minify_js_exclude,
              onChange: excludes => {
                updateSetting('minify_js_exclude', excludes);
              }
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
              label: __('Minify Inline JavaScript', 'simply-static'),
              __nextHasNoMarginBottom: true,
              help: minifyInlineJavascript ? __('Minify Inline JavaScript.', 'simply-static') : __('Don\'t minify Inline JavaScript.', 'simply-static'),
              disabled: 'free' === options.plan || !isPro(),
              checked: minifyInlineJavascript,
              onChange: value => {
                setMinifyInlineJavascript(value);
                updateSetting('minify_inline_js', value);
              }
            })]
          })]
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
              children: [__('Image Optimization', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                title: __('How to optimize images with ShortPixel?', 'simply-static'),
                videoUrl: 'https://youtu.be/OIfKcXz3cxY'
              })]
            })
          }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: [" ", __('Requires Simply Static Pro', 'simply-static')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Optimize Images with ShortPixel?', 'simply-static'),
          __nextHasNoMarginBottom: true,
          help: settings.shortpixel_enabled ? __('Optimize images with the ShortPixel API.', 'simply-static') : __('Don\'t optimize images with the ShortPixel API.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          checked: !!settings.shortpixel_enabled,
          onChange: value => {
            updateSetting('shortpixel_enabled', value);
          }
        }), settings.shortpixel_enabled && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('ShortPixel API Key', 'simply-static'),
            type: "password",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: settings.shortpixel_api_key,
            disabled: 'free' === options.plan || !isPro(),
            onChange: apiKey => {
              updateSetting('shortpixel_api_key', apiKey);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Convert to webP', 'simply-static'),
            __nextHasNoMarginBottom: true,
            help: settings.shortpixel_webp_enabled ? __('Convert images to webp format.', 'simply-static') : __('Don\'t convert images to webp format', 'simply-static'),
            checked: !!settings.shortpixel_webp_enabled,
            disabled: 'free' === options.plan || !isPro(),
            onChange: value => {
              updateSetting('shortpixel_webp_enabled', value);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Backup the original images?', 'simply-static'),
            __nextHasNoMarginBottom: true,
            help: settings.shortpixel_backup_enabled ? __('Back original images.', 'simply-static') : __('Don\'t backup original images.', 'simply-static'),
            checked: !!settings.shortpixel_backup_enabled,
            disabled: 'free' === options.plan || !isPro(),
            onChange: value => {
              updateSetting('shortpixel_backup_enabled', value);
            }
          }), settings.shortpixel_backup_enabled && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
              status: 'warning',
              isDismissible: false,
              children: __('It will preserve every image which might increase your disk space usage.', 'simply-static')
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
              padding: 1
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
              disabled: shortPixelResetting,
              onClick: restoreBackups,
              variant: "secondary",
              children: [!shortPixelResetting && __('Restore Original Images', 'simply-static'), shortPixelResetting && [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Dashicon, {
                icon: "update spin"
              }), __('Restoring...', 'simply-static')]]
            })]
          })]
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
              children: [__('Replace', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                title: __('How to replace WP default paths', 'simply-static'),
                videoUrl: 'https://youtu.be/GedyNJJMGaY'
              })]
            })
          }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: [" ", __('Requires Simply Static Pro', 'simply-static')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('wp-content directory', 'simply-static'),
          help: __('Replace the "wp-content" directory.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          type: "text",
          placeholder: "wp-content",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: wpContentDirectory,
          onChange: directory => {
            updateSetting('wp_content_directory', directory);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('wp-includes directory', 'simply-static'),
          help: __('Replace the "wp-includes" directory.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          type: "text",
          placeholder: "wp-includes",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: wpIncludesDirectory,
          onChange: directory => {
            updateSetting('wp_includes_directory', directory);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('uploads directory', 'simply-static'),
          help: __('Replace the "wp-content/uploads" directory.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          type: "text",
          placeholder: "uploads",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: wpUploadsDirectory,
          onChange: directory => {
            setWpUploadsDirectory(directory);
            updateSetting('wp_uploads_directory', directory);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('plugins directory', 'simply-static'),
          help: __('Replace the "wp-content/plugins" directory.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          type: "text",
          placeholder: "plugins",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: wpPluginsDirectory,
          onChange: directory => {
            setWpPluginsDirectory(directory);
            updateSetting('wp_plugins_directory', directory);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('themes directory', 'simply-static'),
          help: __('Replace the "wp-content/themes" directory.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          type: "text",
          placeholder: "themes",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: wpThemesDirectory,
          onChange: directory => {
            setWpThemesDirectory(directory);
            updateSetting('wp_themes_directory', directory);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalInputControl, {
          label: __('Theme style name', 'simply-static'),
          help: __('Replace the style.css filename.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          type: "text",
          className: "ss-theme-style-name",
          suffix: '.css',
          placeholder: "style",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: themeStyleName,
          onChange: style => {
            setThemeStyleName(style);
            updateSetting('theme_style_name', style);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('Author URL', 'simply-static'),
          help: __('Replace the author url.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          type: "text",
          placeholder: "author",
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          value: authorUrl,
          onChange: url => {
            setAuthorUrl(url);
            updateSetting('author_url', url);
          }
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
              children: [__('Hide', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                title: __('How to hide and disable WP core features', 'simply-static'),
                videoUrl: 'https://youtu.be/GijIsrfFB8o'
              })]
            })
          }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: [" ", __('Requires Simply Static Pro', 'simply-static')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Hide HTML Comments', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: hideComments,
          disabled: 'free' === options.plan || !isPro(),
          onChange: value => {
            setHideComments(value);
            updateSetting('hide_comments', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Hide WordPress Version', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: hideVersion,
          disabled: 'free' === options.plan || !isPro(),
          onChange: value => {
            setHideVersion(value);
            updateSetting('hide_version', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Hide WordPress Generator Meta', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: hideGenerator,
          disabled: 'free' === options.plan || !isPro(),
          onChange: value => {
            setHideGenerator(value);
            updateSetting('hide_generator', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Hide DNS Prefetch WordPress link', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: hidePrefetch,
          disabled: 'free' === options.plan || !isPro(),
          onChange: value => {
            setHidePrefetch(value);
            updateSetting('hide_prefetch', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Hide RSD Header', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: hideRSD,
          disabled: 'free' === options.plan || !isPro(),
          onChange: value => {
            setHideRSD(value);
            updateSetting('hide_rsd', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Hide Emojis if you don\'t use them', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: hideEmojis,
          disabled: 'free' === options.plan || !isPro(),
          onChange: value => {
            setHideEmojis(value);
            updateSetting('hide_emotes', value);
          }
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("b", {
              children: [__('Disable', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_4__["default"], {
                title: __('How to hide and disable WP core features', 'simply-static'),
                videoUrl: 'https://youtu.be/GijIsrfFB8o'
              })]
            })
          }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: [" ", __('Requires Simply Static Pro', 'simply-static')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Disable XML-RPC', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: disableXMLRPC,
          disabled: 'free' === options.plan || !isPro(),
          onChange: value => {
            setDisableXMLRPC(value);
            updateSetting('disable_xmlrpc', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Disable Embed Scripts', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: disableEmbed,
          disabled: 'free' === options.plan || !isPro(),
          onChange: value => {
            setDisableEmbed(value);
            updateSetting('disable_embed', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Disable DB Debug in Frontend', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: disableDbDebug,
          disabled: 'free' === options.plan || !isPro(),
          onChange: value => {
            setDisableDbDebug(value);
            updateSetting('disable_db_debug', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Disable WLW Manifest Scripts', 'simply-static'),
          __nextHasNoMarginBottom: true,
          checked: disableWLW,
          disabled: 'free' === options.plan || !isPro(),
          onChange: value => {
            setDisableWLW(value);
            updateSetting('disable_wlw_manifest', value);
          }
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), settingsSaved && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
        type: "slide-in",
        options: {
          origin: 'top'
        },
        children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "success",
          isDismissible: false,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("p", {
            children: __('Settings saved successfully.', 'simply-static')
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("div", {
      className: "save-settings",
      children: 'pro' === options.plan && isPro() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        onClick: setSavingSettings,
        variant: "primary",
        children: __('Save Settings', 'simply-static')
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Optimize);

/***/ }),

/***/ "./src/settings/pages/SearchSettings.jsx":
/*!***********************************************!*\
  !*** ./src/settings/pages/SearchSettings.jsx ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _components_HelperVideo__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/HelperVideo */ "./src/settings/components/HelperVideo.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);





const {
  __
} = wp.i18n;
function SearchSettings() {
  var _ref, _settings$search_show, _ref2, _settings$search_show2, _settings$search_subm, _settings$search_show3, _settings$search_plac;
  const {
    settings,
    updateSetting,
    saveSettings,
    settingsSaved,
    setSettingsSaved,
    isPro
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  const [useSearch, setUseSearch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [searchType, setSearchType] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('fuse');
  const [isMetaModalOpen, setMetaModalOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const openMetaModal = () => setMetaModalOpen(true);
  const closeMetaModal = () => setMetaModalOpen(false);
  const setSavingSettings = () => {
    saveSettings();
    setSettingsSaved(true);
    setTimeout(function () {
      setSettingsSaved(false);
    }, 2000);
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (settings.use_search) {
      setUseSearch(settings.use_search);
    }
    if (settings.search_type) {
      setSearchType(settings.search_type);
    }
  }, [settings]);

  // Derived flag for showing submit-related controls
  // Default is now: submit button disabled by default (false)
  const showSubmit = (_ref = (_settings$search_show = settings.search_show_submit) !== null && _settings$search_show !== void 0 ? _settings$search_show : settings.search_fuse_show_submit) !== null && _ref !== void 0 ? _ref : false;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
    className: "inner-settings",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("b", {
              children: __('Search', 'simply-static')
            })
          }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: [" ", __('Requires Simply Static Pro', 'simply-static')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Use search?', 'simply-static'),
          __nextHasNoMarginBottom: true,
          help: useSearch ? __('Use search on your static website.', 'simply-static') : __('Don\'t use search on your static website.', 'simply-static'),
          disabled: 'free' === options.plan || !isPro(),
          checked: useSearch,
          onChange: value => {
            setUseSearch(value);
            updateSetting('use_search', value);
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SelectControl, {
          label: __('Search Type', 'simply-static'),
          value: searchType,
          help: __('Decide which search type you want to use. Fuse runs locally based on a file, and Algolia is an external API service.', 'simply-static'),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
          options: [{
            label: 'Fuse JS',
            value: 'fuse'
          }, {
            label: 'Algolia API',
            value: 'algolia'
          }],
          onChange: type => {
            setSearchType(type);
            updateSetting('search_type', type);
          }
        }), useSearch && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
            margin: 3
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Use Submit Button', 'simply-static'),
            __nextHasNoMarginBottom: true,
            help: __('Show or hide the submit button in the search UI.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            checked: (_ref2 = (_settings$search_show2 = settings.search_show_submit) !== null && _settings$search_show2 !== void 0 ? _settings$search_show2 : settings.search_fuse_show_submit) !== null && _ref2 !== void 0 ? _ref2 : false,
            onChange: value => {
              updateSetting('search_show_submit', value);
            }
          }), showSubmit && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
              label: __('Submit button text', 'simply-static'),
              type: "text",
              __next40pxDefaultSize: true,
              __nextHasNoMarginBottom: true,
              placeholder: __('Search', 'simply-static'),
              help: __('Customize the submit button text or HTML (e.g., add an icon).', 'simply-static'),
              disabled: 'free' === options.plan || !isPro(),
              value: (_settings$search_subm = settings.search_submit_text) !== null && _settings$search_subm !== void 0 ? _settings$search_subm : settings.search_fuse_submit_text,
              onChange: val => {
                updateSetting('search_submit_text', val);
              }
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Show excerpt in suggestions/results', 'simply-static'),
            __nextHasNoMarginBottom: true,
            help: __('Enable to display the excerpt below each search suggestion and result item.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            checked: (_settings$search_show3 = settings.search_show_excerpt) !== null && _settings$search_show3 !== void 0 ? _settings$search_show3 : false,
            onChange: value => {
              updateSetting('search_show_excerpt', value);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Input placeholder', 'simply-static'),
            type: "text",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            placeholder: __('Search..', 'simply-static'),
            help: __('Customize the placeholder text for the search input.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            value: (_settings$search_plac = settings.search_placeholder) !== null && _settings$search_plac !== void 0 ? _settings$search_plac : settings.search_fuse_placeholder,
            onChange: val => {
              updateSetting('search_placeholder', val);
            }
          })]
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
      children: [isMetaModalOpen && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Modal, {
        title: __('How to select data with meta tags', 'simply-static'),
        onRequestClose: closeMetaModal,
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('Targeting for excerpt in the meta description tag.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("pre", {
          children: "<meta name=\"description\" content=\"This content is what we want as excerpt\" />"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('Adding such meta in the excerpt field would be:', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("pre", {
          children: "description|content"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('Targeting for title in the property meta tag.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("pre", {
          children: "<meta property=\"og:title\" content=\"This content is what we want as excerpt\" />"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('Adding such meta in the excerpt field would be:', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("pre", {
          children: "property|og:title"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('If the second item (after | ) is not <code>content</code>, we\'ll use it as value of that attribute (<code>property="og:title"</code> in this example) and use <code>content</code> for value.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("strong", {
            children: __('Caution: Use meta tags that exist everywhere for title.', 'simply-static')
          })
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("b", {
                children: __('Indexing', 'simply-static')
              })
            }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                href: "https://simplystatic.com",
                children: [" ", __('Requires Simply Static Pro', 'simply-static')]
              })
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('CSS-Selector for Title', 'simply-static'),
            type: "text",
            placeholder: 'title',
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
              children: [__('Add the CSS selector which contains the title of the page/post', 'simply-static'), ' ', /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
                variant: 'link',
                onClick: openMetaModal,
                children: __('Or meta tags. Click for more information.', 'simply-static')
              })]
            }),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.search_index_title,
            onChange: title => {
              updateSetting('search_index_title', title);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('CSS-Selector for Content', 'simply-static'),
            type: "text",
            placeholder: 'body',
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
              children: [__('Add the CSS selector which contains the content of the page/post.', 'simply-static'), ' ', /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
                variant: 'link',
                onClick: openMetaModal,
                children: __('Or meta tags. Click for more information.', 'simply-static')
              })]
            }),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.search_index_content,
            onChange: content => {
              updateSetting('search_index_content', content);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('CSS-Selector for Excerpt', 'simply-static'),
            type: "text",
            placeholder: '.entry-content',
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
              children: [__('Add the CSS selector which contains the excerpt of the page/post.', 'simply-static'), ' ', /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
                variant: 'link',
                onClick: openMetaModal,
                children: __('Or meta tags. Click for more information.', 'simply-static')
              })]
            }),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.search_index_excerpt,
            onChange: excerpt => {
              updateSetting('search_index_excerpt', excerpt);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextareaControl, {
            label: __('Exclude URLs', 'simply-static'),
            placeholder: "author\narchive\ncategory",
            __nextHasNoMarginBottom: true,
            help: __('Exclude URLs from indexing (one per line). You can use full URLs, parts of an URL or plain words (like stop words).', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.search_excludable,
            onChange: excludes => {
              updateSetting('search_excludable', excludes);
            }
          })]
        })]
      })]
    }), searchType === 'fuse' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("b", {
                children: [__('Fuse.js', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_3__["default"], {
                  title: __('How to add search with FuseJS', 'simply-static'),
                  videoUrl: 'https://youtu.be/K34l1DXjCHk'
                })]
              })
            }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                href: "https://simplystatic.com",
                children: [" ", __('Requires Simply Static Pro', 'simply-static')]
              })
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('CSS-Selector', 'simply-static'),
            type: "text",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            help: __('Add the CSS selector for the <form> element that contains your search input field.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.fuse_selector,
            onChange: selector => {
              updateSetting('fuse_selector', selector);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalNumberControl, {
            label: __('Threshold', 'simply-static'),
            isShiftStepEnabled: true,
            step: 0.1,
            min: 0.1,
            max: 1,
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            help: __(' A threshold of 0.0 requires a perfect match, a threshold of 1.0 would match anything.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.fuse_threshold,
            placeholder: 0.1,
            onChange: threshold => {
              updateSetting('fuse_threshold', threshold);
            }
          })]
        })]
      })]
    }), searchType === 'algolia' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("b", {
                children: [__('Algolia API', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_3__["default"], {
                  title: __('How to add search with the Algolia API', 'simply-static'),
                  videoUrl: 'https://youtu.be/H9PNZSl0KnU'
                })]
              })
            }), ('free' === options.plan || !isPro()) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
                href: "https://simplystatic.com",
                children: [" ", __('Requires Simply Static Pro', 'simply-static')]
              })
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Application ID', 'simply-static'),
            type: "password",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            help: __('Add your Algolia App ID.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.algolia_app_id,
            onChange: app_id => {
              updateSetting('algolia_app_id', app_id);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Admin API Key', 'simply-static'),
            type: "password",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            help: __('Add your Algolia Admin API Key.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.algolia_admin_api_key,
            onChange: api_key => {
              updateSetting('algolia_admin_api_key', api_key);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Search-Only API Key', 'simply-static'),
            type: "password",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            help: __('Add your Algolia Search-Only API Key here. This is the only key that will be visible on your static site.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.algolia_search_api_key,
            onChange: api_key => {
              updateSetting('algolia_search_api_key', api_key);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('Name for your index', 'simply-static'),
            type: "text",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            help: __('Add your Algolia index name here.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.algolia_index,
            onChange: index => {
              updateSetting('algolia_index', index);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
            label: __('CSS-Selector', 'simply-static'),
            type: "text",
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            help: __('Add the CSS selector for the <form> element that contains your search input field.', 'simply-static'),
            disabled: 'free' === options.plan || !isPro(),
            value: settings.algolia_selector,
            onChange: selector => {
              updateSetting('algolia_selector', selector);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: "warning",
            isDismissible: false,
            children: __('If you have multiple search elements with different CSS selectors, separate them by a comma (,) such as: .search-form, .custom-search-form', 'simply-static')
          })]
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), settingsSaved && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
        type: "slide-in",
        options: {
          origin: 'top'
        },
        children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "success",
          isDismissible: false,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
            children: __('Settings saved successfully.', 'simply-static')
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
      className: "save-settings",
      children: 'pro' === options.plan && isPro() && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        onClick: setSavingSettings,
        variant: "primary",
        children: __('Save Settings', 'simply-static')
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SearchSettings);

/***/ }),

/***/ "./src/settings/pages/Utilities.jsx":
/*!******************************************!*\
  !*** ./src/settings/pages/Utilities.jsx ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var _components_HelperVideo__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/HelperVideo */ "./src/settings/components/HelperVideo.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);





const {
  __
} = wp.i18n;
function Utilities() {
  const {
    settings,
    importSettings,
    saveSettings,
    resetSettings,
    migrateSettings,
    resetDatabase,
    resetBackgroundQueue
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_2__.SettingsContext);
  const [isExport, setIsExport] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [isImport, setIsImport] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [isReset, setIsReset] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [isResetDatabase, setIsResetDatabase] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [isMigrate, setIsMigrate] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [hasCopied, setHasCopied] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [importData, setImportData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [isResetBackgroundQueue, setIsResetBackgroundQueue] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const setImportDataValue = event => {
    setImportData(JSON.parse(event.target.value));
  };
  const runImportSettings = () => {
    importSettings(importData);
    setIsImport(true);
    setTimeout(function () {
      setIsImport(false);
    }, 2000);
  };
  const runResetSettings = () => {
    resetSettings();
    setIsReset(true);
    setTimeout(function () {
      setIsReset(false);
    }, 2000);
  };
  const runResetDatabase = () => {
    resetDatabase();
    setIsResetDatabase(true);
    setTimeout(function () {
      setIsResetDatabase(false);
    }, 2000);
  };
  const runMigrateSettings = () => {
    migrateSettings();
    saveSettings();
    setIsMigrate(true);
    setTimeout(function () {
      setIsMigrate(false);
      location.reload();
    }, 2000);
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
    className: "inner-settings",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("b", {
          children: [__('Export', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_3__["default"], {
            title: __('Export & Import settings', 'simply-static'),
            videoUrl: 'https://youtu.be/fmM123Y-gwg'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: !isExport ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
            onClick: setIsExport,
            variant: "primary",
            children: __('Export Settings', 'simply-static')
          })
        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("code", {
              children: JSON.stringify(settings)
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ClipboardButton, {
              variant: "secondary",
              text: JSON.stringify(settings),
              onCopy: () => setHasCopied(true),
              onFinishCopy: () => setHasCopied(false),
              children: hasCopied ? __('Copied!', 'simply-static') : __('Copy export data', 'simply-static')
            })
          })]
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("b", {
          children: [__('Import', 'simply-static'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_components_HelperVideo__WEBPACK_IMPORTED_MODULE_3__["default"], {
            title: __('Export & Import settings', 'simply-static'),
            videoUrl: 'https://youtu.be/fmM123Y-gwg'
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('Paste in the JSON string you got from your export to import all settings for the plugin.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("textarea", {
          rows: "8",
          name: "import-data",
          onChange: setImportDataValue
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
            onClick: runImportSettings,
            variant: "primary",
            children: __('Import Settings', 'simply-static')
          })
        }), isImport ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
          type: "slide-in",
          options: {
            origin: 'top'
          },
          children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: "success",
            isDismissible: false,
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
              children: __('Settings imported successfully.', 'simply-static')
            })
          })
        }) : '']
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("b", {
          children: __('Reset', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('By clicking the "Reset Plugin Settings", you will reset all plugin settings. This can be useful if you want to import a new set of settings or you want a fresh start.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('If you click the "Reset Database Table" button instead, you will keep all your settings, and we will only recreate our DB table.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('If the background process is stuck and your debug log shows "There is already an export running", use the "Reset Background Queue" button to clear the queue and locks.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("p", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
            onClick: runResetSettings,
            variant: "primary",
            children: __('Reset Plugin Settings', 'simply-static')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
            onClick: runResetDatabase,
            className: "reset-db-btn",
            variant: "primary",
            children: __('Reset Database Table', 'simply-static')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
            onClick: () => {
              resetBackgroundQueue();
              setIsResetBackgroundQueue(true);
              setTimeout(() => setIsResetBackgroundQueue(false), 2000);
            },
            className: "reset-bg-btn",
            variant: "primary",
            children: __('Reset Background Queue', 'simply-static')
          })]
        }), isReset ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
          type: "slide-in",
          options: {
            origin: 'top'
          },
          children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: "success",
            isDismissible: false,
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
              children: __('Settings resetted successfully.', 'simply-static')
            })
          })
        }) : '', isResetDatabase ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
          type: "slide-in",
          options: {
            origin: 'top'
          },
          children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: "success",
            isDismissible: false,
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
              children: __('Database table resetted successfully.', 'simply-static')
            })
          })
        }) : '', isResetBackgroundQueue ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
          type: "slide-in",
          options: {
            origin: 'top'
          },
          children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: "success",
            isDismissible: false,
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
              children: __('Background queue reset successfully.', 'simply-static')
            })
          })
        }) : '']
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("b", {
          children: __('Migrate Settings', 'simply-static')
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: __('Migrate all of your settings to Simply Static 3.0', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
            onClick: runMigrateSettings,
            variant: "primary",
            children: __('Migrate settings', 'simply-static')
          })
        }), isMigrate ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
          type: "slide-in",
          options: {
            origin: 'top'
          },
          children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
            status: "success",
            isDismissible: false,
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
              children: __('Settings migration successfully.', 'simply-static')
            })
          })
        }) : '']
      })]
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Utilities);

/***/ }),

/***/ "./src/settings/pages/Workflow.jsx":
/*!*****************************************!*\
  !*** ./src/settings/pages/Workflow.jsx ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _context_SettingsContext__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../context/SettingsContext */ "./src/settings/context/SettingsContext.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);





const {
  __
} = wp.i18n;
function Workflow() {
  const {
    settings,
    updateSetting,
    saveSettings,
    settingsSaved,
    setSettingsSaved,
    isPro
  } = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context_SettingsContext__WEBPACK_IMPORTED_MODULE_3__.SettingsContext);
  const proEnabled = typeof isPro === 'function' ? !!isPro() : true;

  // Local state for token field (labels shown in the UI)
  const [pageSuggestions, setPageSuggestions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]); // array of labels
  const [pagesMap, setPagesMap] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({}); // label -> ID
  const [selectedPageLabels, setSelectedPageLabels] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]); // labels shown as tokens

  // Taxonomies (for taxonomy archives FormTokenField)
  const [taxonomySuggestions, setTaxonomySuggestions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]); // array of labels
  const [taxonomyMap, setTaxonomyMap] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({}); // label -> slug
  const [selectedTaxonomyLabels, setSelectedTaxonomyLabels] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);

  // Master toggle and sitemap toggle
  const [useSingleExports, setUseSingleExports] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [updateXmlSitemap, setUpdateXmlSitemap] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  // Auto export controls
  const [autoExport, setAutoExport] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [autoExportDelay, setAutoExportDelay] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(3);
  // Webhook settings (moved to dedicated card)
  const [webhookUrl, setWebhookUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [webhookEnabledTypes, setWebhookEnabledTypes] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(['export', 'update', 'build', 'single']);
  const [incArchives, setIncArchives] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [incPagination, setIncPagination] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);

  // Builds toggle
  const [useBuilds, setUseBuilds] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);

  // Load pages suggestions (published pages list) and taxonomies
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Initialize master and sitemap toggles from settings
    setUseSingleExports(settings.ss_use_single_exports !== false);
    setUpdateXmlSitemap(!!settings.ss_single_export_add_xml_sitemap);

    // Pages
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: '/simplystatic/v1/pages'
    }).then(list => {
      const labels = list.map(i => i.label);
      const map = {};
      list.forEach(i => map[i.label] = i.value);
      setPageSuggestions(labels);
      setPagesMap(map);

      // Initialize tokens from settings (IDs -> labels)
      const ids = Array.isArray(settings.ss_single_pages) ? settings.ss_single_pages : [];
      if (ids.length > 0) {
        const toLabels = ids.map(id => {
          const entry = list.find(i => String(i.value) === String(id));
          return entry ? entry.label : String(id);
        });
        setSelectedPageLabels(toLabels);
      }
    });

    // Taxonomies
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: '/simplystatic/v1/taxonomies'
    }).then(list => {
      // list: [{label, value: slug}]
      const labels = list.map(i => i.label);
      const map = {};
      list.forEach(i => map[i.label] = i.value);
      setTaxonomySuggestions(labels);
      setTaxonomyMap(map);

      // Initialize taxonomy tokens from settings (slugs -> labels)
      const slugs = Array.isArray(settings.ss_single_taxonomy_archives) ? settings.ss_single_taxonomy_archives : [];
      if (slugs.length > 0) {
        const toLabels = slugs.map(slug => {
          const entry = list.find(i => String(i.value) === String(slug));
          return entry ? entry.label : String(slug);
        });
        setSelectedTaxonomyLabels(toLabels);
      }
    });

    // Initialize toggles from settings; coerce truthiness properly
    setIncArchives(!!settings.ss_single_include_archives || settings.ss_single_include_archives === undefined);
    setIncPagination(!!settings.ss_single_include_pagination || settings.ss_single_include_pagination === undefined);
    // Initialize Builds toggle (default to false when undefined)
    setUseBuilds(!!settings.ss_use_builds);
    // Auto export and delay
    setAutoExport(!!settings.ss_single_auto_export);
    if (typeof settings.ss_single_auto_export_delay !== 'undefined') {
      const d = parseInt(settings.ss_single_auto_export_delay, 10);
      setAutoExportDelay(Number.isNaN(d) ? 3 : Math.max(0, d));
    } else {
      setAutoExportDelay(3);
    }
    // Webhook settings (new)
    setWebhookUrl(settings.ss_webhook_url || settings.ss_single_export_webhook_url || '');
    const defaultTypes = ['export', 'update', 'build', 'single'];
    if (Array.isArray(settings.ss_webhook_enabled_types) && settings.ss_webhook_enabled_types.length) {
      setWebhookEnabledTypes(settings.ss_webhook_enabled_types);
    } else {
      // If legacy single-only webhook existed, default to single only; else enable all.
      setWebhookEnabledTypes(settings.ss_single_export_webhook_url && settings.ss_single_export_webhook_url.length ? ['single'] : defaultTypes);
    }
  }, [settings.ss_single_pages, settings.ss_single_taxonomy_archives, settings.ss_use_builds]);

  // Handlers
  const onChangeTokens = tokens => {
    if (!proEnabled) return;
    setSelectedPageLabels(tokens);
    // Map labels back to IDs using pagesMap; keep unknown tokens as-is if numeric
    const ids = tokens.map(label => {
      if (pagesMap[label]) return pagesMap[label];
      // If label is a number string, keep as number
      const n = parseInt(label, 10);
      return isNaN(n) ? null : n;
    }).filter(v => v !== null);
    updateSetting('ss_single_pages', ids);
  };

  // Handlers for taxonomy token field
  const onChangeTaxonomyTokens = tokens => {
    if (!proEnabled) return;
    setSelectedTaxonomyLabels(tokens);
    const slugs = tokens.map(label => {
      if (taxonomyMap[label]) return taxonomyMap[label];
      // Fall back to using the raw token as slug if it's a known slug present in suggestions map values
      return label;
    }).filter(Boolean);
    updateSetting('ss_single_taxonomy_archives', slugs);
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("strong", {
              children: __('Single Exports', 'simply-static')
            })
          }), ('free' === options.plan || !proEnabled) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: __('Requires Simply Static Pro', 'simply-static')
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Use Single Exports?', 'simply-static'),
          help: useSingleExports ? __('Quickly push updates to your static site while editing pages/posts.', 'simply-static') : __('Do not allow quick updates while editing posts/pages.', 'simply-static'),
          checked: !!useSingleExports,
          disabled: !proEnabled,
          onChange: value => {
            if (!proEnabled) return;
            setUseSingleExports(value);
            updateSetting('ss_use_single_exports', value);
          }
        }), useSingleExports && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Use Auto Export?', 'simply-static'),
            help: autoExport ? __('Automatically schedule a Single Export when a post/page is updated/published.', 'simply-static') : __('Do not automatically run Single Export when a post/page is updated/published.', 'simply-static'),
            checked: !!autoExport,
            disabled: !proEnabled,
            onChange: value => {
              if (!proEnabled) return;
              setAutoExport(value);
              updateSetting('ss_single_auto_export', value);
            }
          }), autoExport && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalInputControl, {
            label: __('Auto export delay (seconds)', 'simply-static'),
            help: __('Delay before the automatic Single Export starts after a change is detected.', 'simply-static'),
            type: "number",
            min: 0,
            value: autoExportDelay,
            disabled: !proEnabled,
            onChange: value => {
              if (!proEnabled) return;
              const n = parseInt(value, 10);
              const safe = Number.isNaN(n) ? 0 : Math.max(0, n);
              setAutoExportDelay(safe);
              updateSetting('ss_single_auto_export_delay', safe);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FormTokenField, {
            label: __('Pages to update', 'simply-static'),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: selectedPageLabels,
            suggestions: pageSuggestions,
            onChange: onChangeTokens,
            help: __('Select which pages should start a Single Export. If none selected, we use your homepage.', 'simply-static'),
            tokenizeOnSpace: false,
            __experimentalExpandOnFocus: true,
            __experimentalShowHowTo: false,
            maxSuggestions: 200,
            className: "horizontal-token-field",
            disabled: !proEnabled
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
            className: 'description',
            children: __('Choose which pages/files should be updated when running a Single Export.', 'simply-static')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
            margin: 2
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FormTokenField, {
            label: __('Taxonomy Archives', 'simply-static'),
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            value: selectedTaxonomyLabels,
            suggestions: taxonomySuggestions,
            onChange: onChangeTaxonomyTokens,
            help: __('Choose which taxonomy archives to include for selected pages/posts (e.g., Categories, Tags, or custom taxonomies).', 'simply-static'),
            tokenizeOnSpace: false,
            __experimentalExpandOnFocus: true,
            __experimentalShowHowTo: false,
            maxSuggestions: 100,
            className: "horizontal-token-field",
            disabled: !proEnabled
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
            className: 'description',
            children: __('Choose which taxonomies should be updated when running a Single Export.', 'simply-static')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
            margin: 2
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Update archives (author/date/post_type)', 'simply-static'),
            help: incArchives ? __('Update archive URLs when running a Single Export.', 'simply-static') : __('Do not update archive URLs.', 'simply-static'),
            checked: !!incArchives,
            disabled: !proEnabled,
            onChange: value => {
              if (!proEnabled) return;
              setIncArchives(value);
              updateSetting('ss_single_include_archives', value);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Update pagination', 'simply-static'),
            help: incPagination ? __('Update pagination URLs when running a Single Export.', 'simply-static') : __('Do not update pagination URLs.', 'simply-static'),
            checked: !!incPagination,
            disabled: !proEnabled,
            onChange: value => {
              if (!proEnabled) return;
              setIncPagination(value);
              updateSetting('ss_single_include_pagination', value);
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: __('Update XML sitemap', 'simply-static'),
            help: updateXmlSitemap ? __('Update XML sitemaps when running a Single Export.', 'simply-static') : __('Do not update XML sitemaps.', 'simply-static'),
            checked: !!updateXmlSitemap,
            disabled: !proEnabled,
            onChange: value => {
              if (!proEnabled) return;
              setUpdateXmlSitemap(value);
              updateSetting('ss_single_export_add_xml_sitemap', value);
            }
          })]
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("strong", {
              children: __('Build Exports', 'simply-static')
            })
          }), ('free' === options.plan || !proEnabled) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: __('Requires Simply Static Pro', 'simply-static')
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
          label: __('Use Build Exports?', 'simply-static'),
          help: useBuilds ? __('Use build exports to quickly update a list of pages and files on your static site.', 'simply-static') : __('Build Exports are disabled.', 'simply-static'),
          checked: !!useBuilds,
          disabled: !proEnabled,
          onChange: value => {
            if (!proEnabled) return;
            setUseBuilds(value);
            updateSetting('ss_use_builds', value);
          }
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 5
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Card, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardHeader, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Flex, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("strong", {
              children: __('Webhook', 'simply-static')
            })
          }), ('free' === options.plan || !proEnabled) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.FlexItem, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ExternalLink, {
              href: "https://simplystatic.com",
              children: __('Requires Simply Static Pro', 'simply-static')
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.CardBody, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.TextControl, {
          label: __('Webhook URL', 'simply-static'),
          placeholder: 'https://example.com/webhook-endpoint',
          value: webhookUrl,
          disabled: !proEnabled,
          onChange: value => {
            if (!proEnabled) return;
            setWebhookUrl(value);
            updateSetting('ss_webhook_url', value);
          },
          help: __('Simply Static will POST a JSON payload to this URL after exports finish.', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          className: 'description',
          children: __('Fire for these export types:', 'simply-static')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
          className: "ss-webhook-types",
          children: ['export', 'update', 'build', 'single'].map(type => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.ToggleControl, {
            label: type === 'export' ? __('Export (full)', 'simply-static') : type === 'update' ? __('Update', 'simply-static') : type === 'build' ? __('Build', 'simply-static') : __('Single', 'simply-static'),
            checked: webhookEnabledTypes.includes(type),
            disabled: !proEnabled,
            onChange: checked => {
              if (!proEnabled) return;
              const next = new Set(webhookEnabledTypes);
              if (checked) next.add(type);else next.delete(type);
              const arr = Array.from(next);
              setWebhookEnabledTypes(arr);
              updateSetting('ss_webhook_enabled_types', arr);
            }
          }, type))
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
          className: 'description',
          children: __('Payload includes event, site_url, export_type, identifiers (when available), timestamp, and success.', 'simply-static')
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
      margin: 10
    }), settingsSaved && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Animate, {
        type: "slide-in",
        options: {
          origin: 'top'
        },
        children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
          status: "success",
          isDismissible: false,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
            children: __('Settings saved successfully.', 'simply-static')
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.__experimentalSpacer, {
        margin: 5
      })]
    }), proEnabled && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
      className: "save-settings",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
        onClick: () => {
          if (!proEnabled) return;
          saveSettings();
          setSettingsSaved(true);
          setTimeout(() => setSettingsSaved(false), 2000);
        },
        variant: "primary",
        children: __('Save Settings', 'simply-static')
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Workflow);

/***/ }),

/***/ "./src/settings/settings.scss":
/*!************************************!*\
  !*** ./src/settings/settings.scss ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["element"];

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

"use strict";
module.exports = window["React"];

/***/ }),

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

"use strict";
module.exports = window["ReactJSXRuntime"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/ensure chunk */
/******/ 	(() => {
/******/ 		__webpack_require__.f = {};
/******/ 		// This file contains only the entry chunk.
/******/ 		// The chunk loading function for additional chunks
/******/ 		__webpack_require__.e = (chunkId) => {
/******/ 			return Promise.all(Object.keys(__webpack_require__.f).reduce((promises, key) => {
/******/ 				__webpack_require__.f[key](chunkId, promises);
/******/ 				return promises;
/******/ 			}, []));
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get javascript chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks
/******/ 		__webpack_require__.u = (chunkId) => {
/******/ 			// return url for filenames based on template
/******/ 			return "" + chunkId + ".js?ver=" + {"vendors-node_modules_custom-media-element_dist_custom-media-element_js":"00f45b77619fbd94429a","vendors-node_modules_hls_js_dist_hls_mjs-node_modules_media-tracks_dist_index_js":"9714c6691d1171b029c0","reactPlayerHls":"4da52c70173fcac296d0","reactPlayerDash":"ddb3e32936c04e440820","reactPlayerMux":"c9180778e6e0af651a1c","reactPlayerYouTube":"576676872b3c389b50fd","reactPlayerVimeo":"2da4d5e44022a58c95fb","reactPlayerWistia":"cef8539f7f4bd14cca0e","reactPlayerSpotify":"67663adaefce4a5f5e6a","reactPlayerTwitch":"0f7f3985c3c92e1aec30","reactPlayerTiktok":"d118f838a6ecd46594c2","reactPlayerPreview":"c424bfcbd3ef586636eb","vendors-node_modules_dashjs_dist_modern_esm_dash_all_min_js":"9fab064934f0812d68f6"}[chunkId] + "";
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get mini-css chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks
/******/ 		__webpack_require__.miniCssF = (chunkId) => {
/******/ 			// return url for filenames based on template
/******/ 			return undefined;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/load script */
/******/ 	(() => {
/******/ 		var inProgress = {};
/******/ 		var dataWebpackPrefix = "simplystatic-settings:";
/******/ 		// loadScript function to load a script via script tag
/******/ 		__webpack_require__.l = (url, done, key, chunkId) => {
/******/ 			if(inProgress[url]) { inProgress[url].push(done); return; }
/******/ 			var script, needAttach;
/******/ 			if(key !== undefined) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				for(var i = 0; i < scripts.length; i++) {
/******/ 					var s = scripts[i];
/******/ 					if(s.getAttribute("src") == url || s.getAttribute("data-webpack") == dataWebpackPrefix + key) { script = s; break; }
/******/ 				}
/******/ 			}
/******/ 			if(!script) {
/******/ 				needAttach = true;
/******/ 				script = document.createElement('script');
/******/ 		
/******/ 				script.charset = 'utf-8';
/******/ 				script.timeout = 120;
/******/ 				if (__webpack_require__.nc) {
/******/ 					script.setAttribute("nonce", __webpack_require__.nc);
/******/ 				}
/******/ 				script.setAttribute("data-webpack", dataWebpackPrefix + key);
/******/ 		
/******/ 				script.src = url;
/******/ 			}
/******/ 			inProgress[url] = [done];
/******/ 			var onScriptComplete = (prev, event) => {
/******/ 				// avoid mem leaks in IE.
/******/ 				script.onerror = script.onload = null;
/******/ 				clearTimeout(timeout);
/******/ 				var doneFns = inProgress[url];
/******/ 				delete inProgress[url];
/******/ 				script.parentNode && script.parentNode.removeChild(script);
/******/ 				doneFns && doneFns.forEach((fn) => (fn(event)));
/******/ 				if(prev) return prev(event);
/******/ 			}
/******/ 			var timeout = setTimeout(onScriptComplete.bind(null, undefined, { type: 'timeout', target: script }), 120000);
/******/ 			script.onerror = onScriptComplete.bind(null, script.onerror);
/******/ 			script.onload = onScriptComplete.bind(null, script.onload);
/******/ 			needAttach && document.head.appendChild(script);
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/publicPath */
/******/ 	(() => {
/******/ 		var scriptUrl;
/******/ 		if (__webpack_require__.g.importScripts) scriptUrl = __webpack_require__.g.location + "";
/******/ 		var document = __webpack_require__.g.document;
/******/ 		if (!scriptUrl && document) {
/******/ 			if (document.currentScript && document.currentScript.tagName.toUpperCase() === 'SCRIPT')
/******/ 				scriptUrl = document.currentScript.src;
/******/ 			if (!scriptUrl) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				if(scripts.length) {
/******/ 					var i = scripts.length - 1;
/******/ 					while (i > -1 && (!scriptUrl || !/^http(s?):/.test(scriptUrl))) scriptUrl = scripts[i--].src;
/******/ 				}
/******/ 			}
/******/ 		}
/******/ 		// When supporting browsers where an automatic publicPath is not supported you must specify an output.publicPath manually via configuration
/******/ 		// or pass an empty string ("") and set the __webpack_public_path__ variable from your code to use your own logic.
/******/ 		if (!scriptUrl) throw new Error("Automatic publicPath is not supported in this browser");
/******/ 		scriptUrl = scriptUrl.replace(/^blob:/, "").replace(/#.*$/, "").replace(/\?.*$/, "").replace(/\/[^\/]+$/, "/");
/******/ 		__webpack_require__.p = scriptUrl;
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"index": 0
/******/ 		};
/******/ 		
/******/ 		__webpack_require__.f.j = (chunkId, promises) => {
/******/ 				// JSONP chunk loading for javascript
/******/ 				var installedChunkData = __webpack_require__.o(installedChunks, chunkId) ? installedChunks[chunkId] : undefined;
/******/ 				if(installedChunkData !== 0) { // 0 means "already installed".
/******/ 		
/******/ 					// a Promise means "currently loading".
/******/ 					if(installedChunkData) {
/******/ 						promises.push(installedChunkData[2]);
/******/ 					} else {
/******/ 						if(true) { // all chunks have JS
/******/ 							// setup Promise in chunk cache
/******/ 							var promise = new Promise((resolve, reject) => (installedChunkData = installedChunks[chunkId] = [resolve, reject]));
/******/ 							promises.push(installedChunkData[2] = promise);
/******/ 		
/******/ 							// start chunk loading
/******/ 							var url = __webpack_require__.p + __webpack_require__.u(chunkId);
/******/ 							// create error before stack unwound to get useful stacktrace later
/******/ 							var error = new Error();
/******/ 							var loadingEnded = (event) => {
/******/ 								if(__webpack_require__.o(installedChunks, chunkId)) {
/******/ 									installedChunkData = installedChunks[chunkId];
/******/ 									if(installedChunkData !== 0) installedChunks[chunkId] = undefined;
/******/ 									if(installedChunkData) {
/******/ 										var errorType = event && (event.type === 'load' ? 'missing' : event.type);
/******/ 										var realSrc = event && event.target && event.target.src;
/******/ 										error.message = 'Loading chunk ' + chunkId + ' failed.\n(' + errorType + ': ' + realSrc + ')';
/******/ 										error.name = 'ChunkLoadError';
/******/ 										error.type = errorType;
/******/ 										error.request = realSrc;
/******/ 										installedChunkData[1](error);
/******/ 									}
/******/ 								}
/******/ 							};
/******/ 							__webpack_require__.l(url, loadingEnded, "chunk-" + chunkId, chunkId);
/******/ 						}
/******/ 					}
/******/ 				}
/******/ 		};
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		// no on chunks loaded
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 		
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunksimplystatic_settings"] = globalThis["webpackChunksimplystatic_settings"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	(() => {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(() => {
"use strict";
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _settings_Settings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./settings/Settings */ "./src/settings/Settings.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);
// eslint-disable-next-line import/no-extraneous-dependencies



if (options.screen === 'simplystatic-settings') {
  let settings = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createRoot)(document.getElementById('simplystatic-settings'));
  settings.render(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_settings_Settings__WEBPACK_IMPORTED_MODULE_1__["default"], {}));
}
})();

/******/ })()
;
//# sourceMappingURL=index.js.map