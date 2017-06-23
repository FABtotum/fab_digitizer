/*!
 * AreaSelect v1.0
 * 
 * Author: Daniel Kesler <kesler.daniel@gmail.com>
 *
 * Copyright (c) 2014-2016 FABtotum
 * Released under the GPLv3 license
 *
 * Date: 2017-01-17
 */

(function (factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as anonymous module.
    define(['jquery'], factory);
  } else if (typeof exports === 'object') {
    // Node / CommonJS
    factory(require('jquery'));
  } else {
    // Browser globals.
    factory(jQuery);
  }
})(function ($) {

  'use strict';

  // Globals
  var $window = $(window);
  var $document = $(document);
  var location = window.location;
  var navigator = window.navigator;
  var ArrayBuffer = window.ArrayBuffer;
  var Uint8Array = window.Uint8Array;
  var DataView = window.DataView;
  var btoa = window.btoa;

  // Constants
  var NAMESPACE = 'areaselect';
  
  // Classes
  var CLASS_MODAL = 'areaselect-modal';
  var CLASS_HIDE = 'areaselect-hide';
  var CLASS_HIDDEN = 'areaselect-hidden';
  var CLASS_INVISIBLE = 'areaselect-invisible';
  var CLASS_MOVE = 'areaselect-move';
  var CLASS_CROP = 'areaselect-crop';
  var CLASS_DISABLED = 'areaselect-disabled';
  var CLASS_BG = 'areaselect-bg';
  
  // RegExps
  var REGEXP_ACTIONS = /^(e|w|s|n|se|sw|ne|nw|all|move|zoom)$/;
  
  // Data keys
  var DATA_ACTION = 'action';
  
  // Events
  var EVENT_MOUSE_DOWN = 'mousedown touchstart pointerdown MSPointerDown';
  var EVENT_MOUSE_MOVE = 'mousemove touchmove pointermove MSPointerMove';
  var EVENT_MOUSE_UP = 'mouseup touchend touchcancel pointerup pointercancel MSPointerUp MSPointerCancel';
  var EVENT_WHEEL = 'wheel mousewheel DOMMouseScroll';
  var EVENT_DBLCLICK = 'dblclick';
  var EVENT_ERROR = 'error.' + NAMESPACE;
  var EVENT_RESIZE = 'resize.' + NAMESPACE; // Bind to window with namespace
  var EVENT_BUILD = 'build.' + NAMESPACE;
  var EVENT_BUILT = 'built.' + NAMESPACE;
  var EVENT_TOUCH = 'touch.' + NAMESPACE;
  var EVENT_MOVE_START = 'movestart.' + NAMESPACE;
  var EVENT_MOVE = 'move.' + NAMESPACE;
  var EVENT_MOVE_END = 'moveend.' + NAMESPACE;
  
    // Actions
  var ACTION_EAST = 'e';
  var ACTION_WEST = 'w';
  var ACTION_SOUTH = 's';
  var ACTION_NORTH = 'n';
  var ACTION_SOUTH_EAST = 'se';
  var ACTION_SOUTH_WEST = 'sw';
  var ACTION_NORTH_EAST = 'ne';
  var ACTION_NORTH_WEST = 'nw';
  var ACTION_ALL = 'all';
  var ACTION_MOVE = 'move';
  var ACTION_ZOOM = 'zoom';
  var ACTION_NONE = 'none';
  
  // Supports
  var SUPPORT_CANVAS = $.isFunction($('<canvas>')[0].getContext);
  var IS_SAFARI_OR_UIWEBVIEW = navigator && /(Macintosh|iPhone|iPod|iPad).*AppleWebKit/i.test(navigator.userAgent);

  // Maths
  var num = Number;
  var min = Math.min;
  var max = Math.max;
  var abs = Math.abs;
  var sin = Math.sin;
  var cos = Math.cos;
  var sqrt = Math.sqrt;
  var round = Math.round;
  var floor = Math.floor;

  // Utilities
  var fromCharCode = String.fromCharCode;

  function isNumber(n) {
    return typeof n === 'number' && !isNaN(n);
  }

  function isUndefined(n) {
    return typeof n === 'undefined';
  }

  function toArray(obj, offset) {
    var args = [];

    // This is necessary for IE8
    if (isNumber(offset)) {
      args.push(offset);
    }

    return args.slice.apply(obj, args);
  }

  
  // Custom proxy to avoid jQuery's guid
  function proxy(fn, context) {
    var args = toArray(arguments, 2);

    return function () {
      return fn.apply(context, args.concat(toArray(arguments)));
    };
  }

  function isCrossOriginURL(url) {
    var parts = url.match(/^(https?:)\/\/([^\:\/\?#]+):?(\d*)/i);

    return parts && (
      parts[1] !== location.protocol ||
      parts[2] !== location.hostname ||
      parts[3] !== location.port
    );
  }

  function addTimestamp(url) {
    var timestamp = 'timestamp=' + (new Date()).getTime();

    return (url + (url.indexOf('?') === -1 ? '?' : '&') + timestamp);
  }

  function getCrossOrigin(crossOrigin) {
    return crossOrigin ? ' crossOrigin="' + crossOrigin + '"' : '';
  }

  function getImageSize(image, callback) {
    var newImage;

    // Modern browsers (ignore Safari, #120 & #509)
    if (image.naturalWidth && !IS_SAFARI_OR_UIWEBVIEW) {
      return callback(image.naturalWidth, image.naturalHeight);
    }

    // IE8: Don't use `new Image()` here (#319)
    newImage = document.createElement('img');

    newImage.onload = function () {
      callback(this.width, this.height);
    };

    newImage.src = image.src;
  }
  
  function AreaSelect(element, options) {
    this.$element = $(element);
    this.options = $.extend({}, AreaSelect.DEFAULTS, $.isPlainObject(options) && options);
    this.isBuilt = false;
    this.cursorX = 0;
    this.cursorY = 0;
    
    this.areaLeft = 0;
    this.areaRight = 0;
    this.areaTop = 0;
    this.areaBottom = 0;
    
    this.zeroX = 0;
    this.zeroY = 0;
    this.isLoaded = false;
    this.isDisabled = false;
    this.isImg = false;
    this.originalUrl = '';
    this.canvas = null;
    this.cropBox = null;
    this.init();
  }
  
  AreaSelect.prototype = {
    constructor: AreaSelect,
    
    init: function () {
      var $this = this.$element;
      var url;

      if ($this.is('img')) {
        this.isImg = true;

        // Should use `$.fn.attr` here. e.g.: "img/picture.jpg"
        this.originalUrl = url = $this.attr('src');

        // Stop when it's a blank image
        if (!url) {
          return;
        }

        // Should use `$.fn.prop` here. e.g.: "http://example.com/img/picture.jpg"
        //url = $this.prop('src');
      } else if ($this.is('canvas') && SUPPORT_CANVAS) {
        url = $this[0].toDataURL();
      }
      
      this.start();
    },
    
    // A shortcut for triggering custom events
    trigger: function (type, data) {
      var e = $.Event(type, data);

      this.$element.trigger(e);

      return e;
    },
    
    start: function () {
      var $image = this.$element;
      var $clone = this.$clone;

      if (!this.isImg) {
        $clone.off(EVENT_ERROR, this.stop);
        $image = $clone;
      }

      this.image = {};

      getImageSize($image[0], $.proxy(function (naturalWidth, naturalHeight) {
        $.extend(this.image, {
          naturalWidth: naturalWidth,
          naturalHeight: naturalHeight,
          aspectRatio: naturalWidth / naturalHeight
        });

        this.isLoaded = true;
        this.build();
      }, this));
    },

    stop: function () {
      this.$clone.remove();
      this.$clone = null;
    },
    
    /*cursor: function(x, y) {
      var options = this.options;
      var canvas = this.canvas;
      
      var width = this.$canvas.width();
      var height = this.$canvas.height();
      
      var max_x = Math.max(options.left, options.right);
      var min_x = Math.min(options.left, options.right);
      var max_y = Math.max(options.top, options.bottom);
      var min_y = Math.min(options.top, options.bottom);
      
      var mx = Math.max(x, min_x);
      var my = Math.max(y, min_y);
      
      mx = Math.min(x, max_x);
      my = Math.min(y, max_y);
      
      this.cursorX = mx;
      this.cursorY = my;
      
      console.log("cursor set:", x, y, "mapped:", mx, my);
      
      var mappedWidth = options.right - options.left;
      var mappedX1 = options.left;
      var mappedX2 = options.right;
      var mappedHeight = options.bottom - options.top;
      var mappedY1 = options.top;
      var mappedY2 = options.bottom;
      
      var offX = Math.min(mappedX1, mappedX2)
      var offY = Math.min(mappedY1, mappedY2)
      
      var px = (mx-offX) / mappedWidth;
      var py = (my-offY) / mappedHeight;
      
      var rx = width * px;
      var ry = height + height * py;
      
      // move the cursor
      this.$cross.css(
        {
          left:rx-8,
          top:ry-8
        }
      );
    },*/
    
    startMove: function(event) {
      var options = this.options;
      var canvas = this.canvas;
      var originalEvent = event.originalEvent;
      var touches = originalEvent && originalEvent.touches;
      var e = event;
      var action;
      var touchesLength;
      var $areaselect = this.$areaselect;
      
      if (touches) {
        touchesLength = touches.length;

        if (touchesLength > 1) {
            return;
        }

        e = touches[0];
      }
      
      action = action || $(e.target).data(DATA_ACTION);
      
      event.preventDefault();
      
      if (REGEXP_ACTIONS.test(action)) {
        if (this.trigger(EVENT_MOVE_START, {
          originalEvent: originalEvent,
          action: action
        }).isDefaultPrevented()) {
          return;
        }

        event.preventDefault();

        this.action = action;

        // IE8  has `event.pageX/Y`, but not `event.originalEvent.pageX/Y`
        // IE10 has `event.originalEvent.pageX/Y`, but not `event.pageX/Y`
        this.startX = e.pageX || originalEvent && originalEvent.pageX;
        this.startY = e.pageY || originalEvent && originalEvent.pageY;
      }
      
      
    },
    
    endMove: function(event) {
      var originalEvent = event.originalEvent;
      var action = this.action;

      if (this.isDisabled) {
        return;
      }

      if (action) {
        event.preventDefault();

        this.action = '';

        this.trigger(EVENT_MOVE_END, {
          originalEvent: originalEvent,
          action: action
        });
      }
    },
    
    move: function(event) {
      var options = this.options;
      var canvas = this.canvas;
      var originalEvent = event.originalEvent;
      var touches = originalEvent && originalEvent.touches;
      var e = event;
      var action = this.action;
      var touchesLength;
      var $areaselect = this.$areaselect;
      
      if (this.isDisabled) {
        return;
      }
      
      if (touches) {
        touchesLength = touches.length;

        if (touchesLength > 1) {
            return;
        }

        e = touches[0];
      }
      
      event.preventDefault();
      
      if(action)
      {
        event.preventDefault();

        this.endX = e.pageX || originalEvent && originalEvent.pageX;
        this.endY = e.pageY || originalEvent && originalEvent.pageY;
        
        this.change(event);
      }
    },
    
    change: function(event) {
      var options = this.options;
      var e = event;
      var action = this.action;
      
      var startPoint = this.toMappedCoordinates(this.startX, this.startY);
      var endPoint = this.toMappedCoordinates(this.endX, this.endY);
      
      var delta = {
        x: endPoint.x - startPoint.x,
        y: startPoint.y - endPoint.y,
      };
      
      var left  = this.areaX, 
          top   = this.areaY, 
          width = this.areaWidth, 
          height = this.areaHeight;
          
      var minX = options.minX;
      var maxX = options.maxX - width;
      var minY = options.minY;
      var maxY = options.maxY - height;
      
      var maxHeight;
      var maxWidth;
      
      switch(action)
      {
        case ACTION_ALL:
          left += delta.x;
          top  += delta.y;
          break;
        case ACTION_EAST:  // right
          maxWidth = min(options.maxWidth, options.maxX - left);
          width += delta.x;
          break;
        case ACTION_WEST:  // left
          maxWidth = min(options.maxWidth, left - minX + width);
          width -= delta.x;
          left += delta.x;
          break;
        case ACTION_SOUTH: // down
          maxHeight = min(top - options.minY + height, options.maxHeight);
          height -= delta.y;
          top += delta.y;
          break;
        case ACTION_NORTH: // up
          maxHeight = min(options.maxY - top, options.maxHeight)
          height += delta.y;
          break;
        case ACTION_SOUTH_EAST: // down-right
          maxWidth = min(options.maxWidth, options.maxX - left);
          maxHeight = min(top - options.minY + height, options.maxHeight);
          height -= delta.y;
          top += delta.y;
          width += delta.x;
          break;
        case ACTION_SOUTH_WEST: // down-left
          maxWidth = min(options.maxWidth, left - minX + width);
          maxHeight = min(top - options.minY + height, options.maxHeight);
          height -= delta.y;
          top += delta.y;
          width -= delta.x;
          left += delta.x;
          break;
        case ACTION_NORTH_EAST: // up-right
          maxWidth = min(options.maxWidth, options.maxX - left);
          maxHeight = min(options.maxY - top, options.maxHeight)
          height += delta.y;
          width += delta.x;
          break;
        case ACTION_NORTH_WEST: // up-left
          maxWidth = min(options.maxWidth, left - minX + width);
          maxHeight = min(options.maxY - top, options.maxHeight)
          height += delta.y;
          width -= delta.x;
          left += delta.x;
          break;
      };
      
      console.log(left, top, width, height);
      
      if(height > maxHeight)
        height = maxHeight;
        
      if(width > maxWidth)
        width = maxWidth;
      
      if(left < minX)
        left = minX;
        
      if(left > maxX)
        left = maxX;
        
      if(top < minY)
        top = minY;
        
      if(top > maxY)
        top = maxY;
      
      this.areaWidth = width;
      this.areaX = left;
      this.areaHeight = height;
      this.areaY = top;
      
      this.renderAreaBox();
      
      this.startX = this.endX;
      this.startY = this.endY;
    },
    
    renderAreaBox: function () {
      
      var p1 = this.fromMappedCoordinates(this.areaX, this.areaY)
      var p2 = this.fromMappedSize(this.areaWidth, this.areaHeight)
      
      this.$areaBox.css({
        width: p2.width,
        height: p2.height,
        left: p1.x,
        top: p1.y
      });
    },
    
    toMappedCoordinates: function(x, y)
    {
      var options = this.options;
      var offset2 = this.$canvas.offset();
      var width = this.$canvas.width();
      var height = this.$canvas.height();
      
      var rx = x - offset2.left;
      var ry = y - offset2.top;
      
      rx = Math.max(rx, 0);
      ry = Math.max(ry, 0);
      
      rx = Math.min(rx, width);
      ry = Math.min(ry, height);
      
      var px = rx / width;
      var py = ry / height;   
      
      var mappedWidth = options.mappedWidth;
      var mappedHeight = options.mappedHeight;
      
      var data = {
        x: px*mappedWidth,
        y: py*mappedHeight
      };
      
      console.log(x,y, '->', rx, ry);
      
      return data;
    },

    fromMappedSize: function(width, height)
    {
      var options = this.options;
      var mappedWidth = options.mappedWidth;
      var mappedHeight = options.mappedHeight;
      
      var ratio = this.$canvas.width() / mappedWidth;
      
      var w = width * ratio;
      var h = height * ratio;
      
      var size = {
        width : w,
        height : h
      };
      
      return size;
    },
    
    fromMappedCoordinates: function(x, y)
    {
      var options = this.options;
      
      var width = this.$canvas.width();
      var height = this.$canvas.height();
      
      var mappedWidth = options.mappedWidth;
      var mappedHeight = options.mappedHeight;
      
      var px = (x) / mappedWidth;
      var py = (mappedHeight- y - this.areaHeight) / mappedHeight;
      
      var rx = width * px;
      var ry = height * py;
      
      var point = {
        x : rx,
        y : ry
      };
      
      return point;
    },
    
    applyLimits: function(x, y)
    {
      /*
      var options = this.options;
      var max_x = options.minX + options.maxWidth;
      var min_x = options.minX;
      var max_y = options.minY + options.maxHeight;
      var min_y = options.minY;
      
      var mx = Math.max(x, min_x);
      var my = Math.max(y, min_y);
      
      mx = Math.min(mx, max_x);
      my = Math.min(my, max_y);
      */
      
      var point = {
         x: x,
         y: y
      };
      
      return point;
    },
    
    /*touch: function(event) {
      var options = this.options;
      var canvas = this.canvas;
      var originalEvent = event.originalEvent;
      var touches = originalEvent && originalEvent.touches;
      var e = event;
      var touchesLength;
      var $areaselect = this.$areaselect;
      
      if (this.isDisabled) {
        return;
      }
      
      if (touches) {
        touchesLength = touches.length;

        if (touchesLength > 1) {
            return;
        }

        e = touches[0];
      }
      
      event.preventDefault();
      
      // IE8  has `event.pageX/Y`, but not `event.originalEvent.pageX/Y`
      // IE10 has `event.originalEvent.pageX/Y`, but not `event.pageX/Y`
      this.touchX = e.pageX || originalEvent && originalEvent.pageX;
      this.touchY = e.pageY || originalEvent && originalEvent.pageY;
      
      var offset2 = this.$canvas.offset();
      var width = this.$canvas.width();
      var height = this.$canvas.height();
      
      var rx = this.touchX-offset2.left;
      var ry = this.touchY-offset2.top;
      
      rx = Math.max(rx, 0);
      ry = Math.max(ry, 0);
      
      rx = Math.min(rx, width);
      ry = Math.min(ry, height);
      
      var px = rx / width;
      var py = ry / height;      
      
      // trigger touch event with mapped coordinates
      
      var mappedWidth = options.right - options.left;
      var mappedX = options.left;
      var mappedHeight = options.bottom - options.top;
      var mappedY = options.top;
      
      var data = {
        x: mappedX + px*mappedWidth,
        y: mappedY + py*mappedHeight
      };
      
      var execute = true;
      if(options.touch)
      {
        var data_abs = {
          x: data.x - this.zeroX,
          y: data.y - this.zeroY
        };
        
        execute = options.touch(data_abs);
      }
      
      if(execute)
      {
        this.cursor(data.x, data.y);
        this.trigger(EVENT_TOUCH, data);
      }
    },*/
        
    build: function () {
      
      var options = this.options;
      var $this = this.$element;
      var $clone = this.$clone;
      var $areaselect;
      var $face;
      var $areaBox;

      // Unbuild first when replace
      if (this.isBuilt) {
        this.unbuild();
      }

      // Create areaselect elements
      this.$container = $this.parent();
      this.$areaselect = $areaselect = $(AreaSelect.TEMPLATE);
      this.$canvas = $areaselect.find('.areaselect-canvas');
      this.$cross = $areaselect.find('.areaselect-cross');
      this.$face = $face = $areaselect.find('.areaselect-face');
      this.$areaBox = $areaBox = $areaselect.find('.areaselect-drag-box');

      if (!options.guides) {
        $areaselect.find('.areaselect-dashed').addClass(CLASS_HIDDEN);
      }

      if (!options.center) {
        $areaselect.find('.areaselect-center').addClass(CLASS_HIDDEN);
      }

      if (!options.highlight) {
        $face.addClass(CLASS_INVISIBLE);
      }

      if (options.background) {
        $areaselect.addClass(CLASS_BG);
      }

      // Hide the original image
      $this.addClass(CLASS_HIDE).after($areaselect);
      $this.removeClass(CLASS_HIDE);
      
      if (options.disabled)
      {
        this.isDisabled = true;
        $areaselect.addClass(CLASS_DISABLED);
      }
      
      if( (options.initWidth != 0) || (options.initHeight != 0))
      {
        this.areaX = options.initX;
        this.areaY = options.initY;
        this.areaWidth = options.initWidth;
        this.areaHeight = options.initHeight;
      }
      else
      {
        this.areaX = options.minX;
        this.areaY = options.minY;
        this.areaWidth = options.maxWidth;
        this.areaHeight = options.maxHeight;
      }
      
      this.bind();
      this.initContainer();
      this.initCanvas();
      this.isBuilt = true;
      
      this.renderAreaBox();
    },
    
    unbuild: function () {
      this.isBuilt = false;
    },
    
    bind: function () {
      var options = this.options;
      var $this = this.$element;
      var $areaselect = this.$areaselect;

      $areaselect.on(EVENT_MOUSE_DOWN, $.proxy(this.startMove, this));

      $document.
        on(EVENT_MOUSE_MOVE, (this._moveove = proxy(this.move, this))).
        on(EVENT_MOUSE_UP, (this._moveEnd = proxy(this.endMove, this)));


      $window.on(EVENT_RESIZE, (this._resize = proxy(this.resize, this)));
    },

    unbind: function () {
      var options = this.options;
      var $this = this.$element;
      var $areaselect = this.$areaselect;

      $areaselect.off(EVENT_MOUSE_DOWN, this.touch);

      if (options.responsive) {
        $window.off(EVENT_RESIZE, this._resize);
      }
    },

    resize: function () {
      var $container = this.$container;
      var container = this.container;
      var ratio;

      // Check `container` is necessary for IE8
      if (!container) {
        console.log('skipping NO CONTAINER');
        return;
      }

      if( $container.width() < 100 )
        return;

      ratio = $container.width() / container.width;

      // Resize when width changed or height changed
      if (ratio !== 1 || $container.height() !== container.height) {
        this.initContainer();
        this.initCanvas();
      }
      
    },
        
    // Enable (unfreeze) the areaselect
    enable: function () {
      if (this.isBuilt) {
        this.isDisabled = false;
        this.$areaselect.removeClass(CLASS_DISABLED);
      }
    },

    // Disable (freeze) the areaselect
    disable: function () {
      if (this.isBuilt) {
        this.isDisabled = true;
        this.$areaselect.addClass(CLASS_DISABLED);
      }
    },
    
    initContainer: function () {
      var options = this.options;
      var $this = this.$element;
      var $container = this.$container;
      var $areaselect = this.$areaselect;

      $areaselect.addClass(CLASS_HIDDEN);

      var width = $container.width();
      var height = $container.height();

      $areaselect.css((this.container = {
        width: max($container.width(), num(options.minContainerWidth) || 200),
        height: max($container.height(), num(options.minContainerHeight) || 100)
      }));

      // Prevent flickering on rezise if the container was hidden
      if(width != 0 && height != 0)
      {
        $areaselect.removeClass(CLASS_HIDDEN);
      }
    },
    
    // Canvas (image wrapper)
    initCanvas: function () {
      var options = this.options;
      var container = this.container;
      var containerWidth = container.width;
      var containerHeight = container.height;
      var image = this.image;
      var imageNaturalWidth = image.naturalWidth;
      var imageNaturalHeight = image.naturalHeight;
      var is90Degree = abs(image.rotate) === 90;
      var naturalWidth = is90Degree ? imageNaturalHeight : imageNaturalWidth;
      var naturalHeight = is90Degree ? imageNaturalWidth : imageNaturalHeight;
      var aspectRatio = naturalWidth / naturalHeight;
      var canvasWidth = containerWidth;
      var canvasHeight = containerHeight;
      var canvas;

      canvas = {
        naturalWidth: naturalWidth,
        naturalHeight: naturalHeight,
        aspectRatio: aspectRatio,
        width: canvasWidth,
        height: canvasHeight
      };

      canvas.oldLeft = canvas.left = (containerWidth - canvasWidth) / 2;
      canvas.oldTop = canvas.top = (containerHeight - canvasHeight) / 2;

      this.canvas = canvas;
    },
    
  }
  
  AreaSelect.DEFAULTS = {
    // Show the dashed lines for guiding
    guides: true,

    // Show the center indicator for guiding
    center: true,

    // Show the white modal to highlight the crop box
    highlight: true,

    // Show the grid background
    background: false,
    
    // Initialize disabled areaselect
    disabled: false,
    
    // Extra Limits
    minX    : 0,
    maxX    : 100,
    minY    : 0,
    maxY    : 100,
    maxWidth  : 100,
    maxHeight : 100,
    
    // Dimension mapping
    useMappedDimensions : false,
    mappedWidth  : 100,
    mappedHeight : 100,
    
    initX      : 0,
    initY      : 0,
    initWidth  : 50, 
    initHeight : 50,
  };
  
  AreaSelect.TEMPLATE = (
    '<div class="areaselect-container">' +
      '<div class="areaselect-wrap-box">' +
        '<div class="areaselect-canvas"></div>' +
        
        '<div>' +
          '<span class="areaselect-dashed dashed-h"></span>' +
          '<span class="areaselect-dashed dashed-v"></span>' +
          '<span class="areaselect-center"></span>' +
          '<span class="areaselect-face"></span>' +
        '</div>' +
        
        '<div class="areaselect-drag-box" data-action="all">' +
          '<span class="areaselect-line line-e" data-action="e"></span>' +
          '<span class="areaselect-line line-n" data-action="n"></span>' +
          '<span class="areaselect-line line-w" data-action="w"></span>' +
          '<span class="areaselect-line line-s" data-action="s"></span>' +
          '<span class="areaselect-point point-e" data-action="e"></span>' +
          '<span class="areaselect-point point-n" data-action="n"></span>' +
          '<span class="areaselect-point point-w" data-action="w"></span>' +
          '<span class="areaselect-point point-s" data-action="s"></span>' +
          '<span class="areaselect-point point-ne" data-action="ne"></span>' +
          '<span class="areaselect-point point-nw" data-action="nw"></span>' +
          '<span class="areaselect-point point-sw" data-action="sw"></span>' +
          '<span class="areaselect-point point-se" data-action="se"></span>' +
        '</div>' + 
      '</div>' +
    '</div>'
  );
  
  AreaSelect.setDefaults = function (options) {
    $.extend(AreaSelect.DEFAULTS, options);
  };
  
  // Save the other AreaSelect
  AreaSelect.other = $.fn.areaselect;
  
  // Register as jQuery plugin
  $.fn.areaselect = function (option) {
    var args = toArray(arguments, 1);
    var result;

    this.each(function () {
      var $this = $(this);
      var data = $this.data(NAMESPACE);
      var options;
      var fn;

      if (!data) {
        if (/destroy/.test(option)) {
          return;
        }

        options = $.extend({}, $this.data(), $.isPlainObject(option) && option);
        $this.data(NAMESPACE, (data = new AreaSelect(this, options)));
      }

      if (typeof option === 'string' && $.isFunction(fn = data[option])) {
        result = fn.apply(data, args);
      }
    });

    return isUndefined(result) ? this : result;
  };

  $.fn.areaselect.Constructor = AreaSelect;
  $.fn.areaselect.setDefaults = AreaSelect.setDefaults;

  // No conflict
  $.fn.areaselect.noConflict = function () {
    $.fn.areaselect = AreaSelect.other;
    return this;
  };
  
});
