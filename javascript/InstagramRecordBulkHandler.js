(function($) {
  var colors = {
    deactivated: "#F46B00",
    active: "#1f9433"
  };

  $.entwine("ss", function($) {
    $("td.col-Status").entwine({
      onmatch: function() {
        var color = colors[this.text()];
        console.log(color);
        if (typeof color !== "undefined") {
          this.css({
            "background-color": color,
            color: "#FFFFFF"
          });
        }
      }
    });
  });
})(jQuery);
