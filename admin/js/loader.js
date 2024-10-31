(function () {
  document.currentScript =
    document.currentScript ||
    (function () {
      var scripts = document.getElementsByTagName('script');
      return scripts[scripts.length - 1];
    })();
  var allAttrs = document.currentScript.attributes;
  var locationID = allAttrs['data-location-id'];
  var cdnURL = allAttrs['data-cdn-url'] && allAttrs['data-cdn-url'].value;
  if (!allAttrs['data-resources-url']) {
    var indexOfLastPath = document.currentScript.src.lastIndexOf('/');
    var sourceURL = document.currentScript.src.substring(0, indexOfLastPath);
    document.currentScript.setAttribute('data-resources-url', sourceURL + '/chat-widget/loader.js');
  }

  if (!cdnURL) {
    if (document.currentScript.src) {
      var lastPathIndex = document.currentScript.src.lastIndexOf('/');
      cdnURL = document.currentScript.src.substring(0, lastPathIndex);
    } else {
      cdnURL = window.location.origin;
    }
  }

  function loadWidget() {
    var moduleScript = document.createElement('script');
    moduleScript.type = 'module';
    moduleScript.src = cdnURL + '/chat-widget/chat-widget.esm.js';

    var plainScript = document.createElement('script');
    plainScript.noModule = true;

    plainScript.src = cdnURL + '/chat-widget/chat-widget.js';
    plainScript.setAttribute('data-resources-url', cdnURL + '/chat-widget/chat-widget.js');
    plainScript.setAttribute('data-stencil-namespace', cdnURL + 'chat-widget');
    document.head.appendChild(moduleScript);
    document.head.appendChild(plainScript);
    var nonCustomElement = document.querySelector('div[data-chat-widget]');

    if (nonCustomElement !== null && document.getElementsByTagName('chat-widget').length === 0) {
      allAttrs = nonCustomElement.attributes;
      locationID = allAttrs['data-location-id'];
      try {
        if (!!nonCustomElement.remove) nonCustomElement.remove();
        else nonCustomElement.parentNode.removeChild(nonCustomElement);
      } catch (error) {}
    }
    if (!!locationID) {
      var textWidget = document.createElement('chat-widget');

      for (var attrIndex = allAttrs.length - 1; attrIndex >= 0; attrIndex--) {
        var attributeName = allAttrs[attrIndex].name;
        var attributeValue = allAttrs[attrIndex].value;
        try {
          attributeName = attributeName.replace('data-', '');
        } catch (e) {
          console.log(e, 'Fail to load widget');
        }
        textWidget.setAttribute(attributeName, attributeValue);
      }

      document.body.appendChild(textWidget);
    }
  }

  if (document.readyState !== 'loading') {
    setTimeout(function () {
      loadWidget();
    }, 0);
  } else {
    //somehow window.addEventListener not working with rocket loader
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(function () {
        loadWidget();
      }, 0);
    });
  }
})();
