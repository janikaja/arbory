jQuery(function(){jQuery("body").on("loadingstart",".button",function(e){var a=jQuery(e.target);if(!a.hasClass("loading")){a.addClass("loading"),a.data("disabled-before-loading",a.prop("disabled")),a.prop("disabled",!0);var d=jQuery("<i />").addClass("loader fa fa-spin fa-spinner");a.append(d)}}),jQuery("body").on("loadingend",".button",function(e){var a=jQuery(e.target);a.find(".loader").remove();var d=a.data("disabled-before-loading");typeof d<"u"&&(d||a.prop("disabled",!1)),a.removeClass("loading")})});
