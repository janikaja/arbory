jQuery(document).ready(function(){$(".js-accordion-trigger").on("click",function(o){o.preventDefault();const c=150;var n=$(o.target).closest(".accordion"),e=$(n).children(".body"),a=$(n).find(".fa");e.slideToggle(c),a.toggleClass("fa-minus"),a.toggleClass("fa-plus")})});