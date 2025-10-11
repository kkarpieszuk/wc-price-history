/*
 * ATTENTION: An "eval-source-map" devtool has been used.
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file with attached SourceMaps in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/components/LowestPriceText.js":
/*!*******************************************!*\
  !*** ./src/components/LowestPriceText.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ LowestPriceText)\n/* harmony export */ });\nfunction LowestPriceText(props) {\n  // Format original price to 2 decimal places, it comes as a string.\n  var originalPriceFormatted = parseFloat(props.originalPrice).toFixed(2);\n  var priceFormatted = \"\".concat(originalPriceFormatted, \" \").concat(wc_price_history_react_main.currency_symbol);\n  var text = wc_price_history_react_main.display_text.replace('{days}', wc_price_history_react_main.last_days).replace('{price}', priceFormatted);\n  return /*#__PURE__*/React.createElement(\"div\", null, text);\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvY29tcG9uZW50cy9Mb3dlc3RQcmljZVRleHQuanMiLCJtYXBwaW5ncyI6Ijs7OztBQUNlLFNBQVNBLGVBQWVBLENBQUVDLEtBQUssRUFBRztFQUNoRDtFQUNBLElBQU1DLHNCQUFzQixHQUFHQyxVQUFVLENBQUNGLEtBQUssQ0FBQ0csYUFBYSxDQUFDLENBQUNDLE9BQU8sQ0FBQyxDQUFDLENBQUM7RUFFekUsSUFBTUMsY0FBYyxNQUFBQyxNQUFBLENBQU1MLHNCQUFzQixPQUFBSyxNQUFBLENBQUlDLDJCQUEyQixDQUFDQyxlQUFlLENBQUU7RUFFakcsSUFBTUMsSUFBSSxHQUFHRiwyQkFBMkIsQ0FBQ0csWUFBWSxDQUNwREMsT0FBTyxDQUFDLFFBQVEsRUFBRUosMkJBQTJCLENBQUNLLFNBQVMsQ0FBQyxDQUN4REQsT0FBTyxDQUFDLFNBQVMsRUFBRU4sY0FBZSxDQUFDO0VBRXBDLG9CQUFPUSxLQUFBLENBQUFDLGFBQUEsY0FDTkwsSUFDSyxDQUFDO0FBQ1IiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvY29tcG9uZW50cy9Mb3dlc3RQcmljZVRleHQuanM/OTczNSJdLCJzb3VyY2VzQ29udGVudCI6WyJcbmV4cG9ydCBkZWZhdWx0IGZ1bmN0aW9uIExvd2VzdFByaWNlVGV4dCggcHJvcHMgKSB7XG5cdC8vIEZvcm1hdCBvcmlnaW5hbCBwcmljZSB0byAyIGRlY2ltYWwgcGxhY2VzLCBpdCBjb21lcyBhcyBhIHN0cmluZy5cblx0Y29uc3Qgb3JpZ2luYWxQcmljZUZvcm1hdHRlZCA9IHBhcnNlRmxvYXQocHJvcHMub3JpZ2luYWxQcmljZSkudG9GaXhlZCgyKTtcblxuXHRjb25zdCBwcmljZUZvcm1hdHRlZCA9IGAke29yaWdpbmFsUHJpY2VGb3JtYXR0ZWR9ICR7d2NfcHJpY2VfaGlzdG9yeV9yZWFjdF9tYWluLmN1cnJlbmN5X3N5bWJvbH1gO1xuXG5cdGNvbnN0IHRleHQgPSB3Y19wcmljZV9oaXN0b3J5X3JlYWN0X21haW4uZGlzcGxheV90ZXh0XG5cdC5yZXBsYWNlKCd7ZGF5c30nLCB3Y19wcmljZV9oaXN0b3J5X3JlYWN0X21haW4ubGFzdF9kYXlzKVxuXHQucmVwbGFjZSgne3ByaWNlfScsIHByaWNlRm9ybWF0dGVkICk7XG5cblx0cmV0dXJuIDxkaXY+e1xuXHRcdHRleHRcblx0fTwvZGl2Pjtcbn0iXSwibmFtZXMiOlsiTG93ZXN0UHJpY2VUZXh0IiwicHJvcHMiLCJvcmlnaW5hbFByaWNlRm9ybWF0dGVkIiwicGFyc2VGbG9hdCIsIm9yaWdpbmFsUHJpY2UiLCJ0b0ZpeGVkIiwicHJpY2VGb3JtYXR0ZWQiLCJjb25jYXQiLCJ3Y19wcmljZV9oaXN0b3J5X3JlYWN0X21haW4iLCJjdXJyZW5jeV9zeW1ib2wiLCJ0ZXh0IiwiZGlzcGxheV90ZXh0IiwicmVwbGFjZSIsImxhc3RfZGF5cyIsIlJlYWN0IiwiY3JlYXRlRWxlbWVudCJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./src/components/LowestPriceText.js\n\n}");

/***/ }),

/***/ "./src/main.js":
/*!*********************!*\
  !*** ./src/main.js ***!
  \*********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _components_LowestPriceText__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/LowestPriceText */ \"./src/components/LowestPriceText.js\");\n/* global wc_price_history_react_main */\n\n\nvar _wp$element = wp.element,\n  createRoot = _wp$element.createRoot,\n  createElement = _wp$element.createElement;\n// I18n.\nvar __ = wp.i18n.__;\nvar selector = \".wc-price-history.prior-price.lowest\";\nvar domNodes = document.querySelectorAll(selector);\ndomNodes.forEach(function (node) {\n  var root = createRoot(node);\n  root.render(/*#__PURE__*/React.createElement(_components_LowestPriceText__WEBPACK_IMPORTED_MODULE_0__[\"default\"], node.dataset));\n});//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvbWFpbi5qcyIsIm1hcHBpbmdzIjoiOztBQUFBOztBQUUyRDtBQUUzRCxJQUFBQyxXQUFBLEdBQXNDQyxFQUFFLENBQUNDLE9BQU87RUFBeENDLFVBQVUsR0FBQUgsV0FBQSxDQUFWRyxVQUFVO0VBQUVDLGFBQWEsR0FBQUosV0FBQSxDQUFiSSxhQUFhO0FBQ2pDO0FBQ0EsSUFBUUMsRUFBRSxHQUFLSixFQUFFLENBQUNLLElBQUksQ0FBZEQsRUFBRTtBQUVWLElBQU1FLFFBQVEsR0FBRyxzQ0FBc0M7QUFFdkQsSUFBTUMsUUFBUSxHQUFHQyxRQUFRLENBQUNDLGdCQUFnQixDQUFDSCxRQUFRLENBQUM7QUFFcERDLFFBQVEsQ0FBQ0csT0FBTyxDQUFDLFVBQUFDLElBQUksRUFBSTtFQUN4QixJQUFNQyxJQUFJLEdBQUdWLFVBQVUsQ0FBQ1MsSUFBSSxDQUFDO0VBRTdCQyxJQUFJLENBQUNDLE1BQU0sY0FBQ0MsS0FBQSxDQUFBWCxhQUFBLENBQUNMLG1FQUFlLEVBQUthLElBQUksQ0FBQ0ksT0FBVSxDQUFDLENBQUM7QUFDbkQsQ0FBQyxDQUFDIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vc3JjL21haW4uanM/NTZkNyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKiBnbG9iYWwgd2NfcHJpY2VfaGlzdG9yeV9yZWFjdF9tYWluICovXG5cbmltcG9ydCBMb3dlc3RQcmljZVRleHQgZnJvbSAnLi9jb21wb25lbnRzL0xvd2VzdFByaWNlVGV4dCc7XG5cbmNvbnN0IHsgY3JlYXRlUm9vdCwgY3JlYXRlRWxlbWVudCB9ID0gd3AuZWxlbWVudDtcbi8vIEkxOG4uXG5jb25zdCB7IF9fIH0gPSB3cC5pMThuO1xuXG5jb25zdCBzZWxlY3RvciA9IFwiLndjLXByaWNlLWhpc3RvcnkucHJpb3ItcHJpY2UubG93ZXN0XCI7XG5cbmNvbnN0IGRvbU5vZGVzID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbChzZWxlY3Rvcik7XG5cbmRvbU5vZGVzLmZvckVhY2gobm9kZSA9PiB7XG5cdGNvbnN0IHJvb3QgPSBjcmVhdGVSb290KG5vZGUpO1xuXG5cdHJvb3QucmVuZGVyKDxMb3dlc3RQcmljZVRleHQgey4uLm5vZGUuZGF0YXNldH0gLz4pO1xufSk7XG4iXSwibmFtZXMiOlsiTG93ZXN0UHJpY2VUZXh0IiwiX3dwJGVsZW1lbnQiLCJ3cCIsImVsZW1lbnQiLCJjcmVhdGVSb290IiwiY3JlYXRlRWxlbWVudCIsIl9fIiwiaTE4biIsInNlbGVjdG9yIiwiZG9tTm9kZXMiLCJkb2N1bWVudCIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJmb3JFYWNoIiwibm9kZSIsInJvb3QiLCJyZW5kZXIiLCJSZWFjdCIsImRhdGFzZXQiXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./src/main.js\n\n}");

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
/************************************************************************/
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
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
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
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval-source-map devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./src/main.js");
/******/ 	
/******/ })()
;