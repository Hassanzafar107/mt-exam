// jQuery(document).ready(function ($) {
//   var table = $("#propertiesTable").DataTable({
//     pageLength: 5,
//     dom: "Bfrtip",
//     buttons: [
//       {
//         extend: "csvHtml5",
//         title: "Properties",
//         text: "Export to CSV",
//       },
//     ],
//   });

//   function fetchProperties() {
//     let maxPrice = $("#priceMax").val();
//     $("#priceLabel").text(maxPrice);

//     $.ajax({
//       url: hzAjax.ajaxurl,
//       method: "POST",
//       data: {
//         action: "hz_filter_properties",
//         max_price: maxPrice,
//       },
//       success: function (response) {
//         table.clear().rows.add(response).draw();
//       },
//     });
//   }

//   // Initial load
//   fetchProperties();

//   // On slider change
//   $("#priceMax").on("input change", fetchProperties);
// });
