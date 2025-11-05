/**
 * @file
 * Js file to handle filter state.
 */

(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.views_year_filter_datepicker = {
    attach: function (context, settings) {
    var availableYears = settings.views_year_filter.uniqueYears; // Define your array of years
    var startYear = availableYears[0];
    var endYear = availableYears[Math.min(11, availableYears.length - 1)];
    var currentPage = 0;
    var yearsPerPage = 12;
    var totalPages = Math.ceil(availableYears.length / yearsPerPage);
    $('.js-datepicker-years-filter').datepicker({
      format: 'yyyy',
      minViewMode: 'years',
      autoclose: true,
      
    }).on('show', function (e) {
        var picker = $('.datepicker-years .table-condensed');
        picker.find('.datepicker-switch,.prev, .next,.prevcustom, .nextcustom,tbody').remove();
        var navRow = $('<tr>').appendTo(picker.find('thead'));
        if(totalPages > 1){
          $('<th>').addClass('prevcustom').attr('tabindex', 0).text('«').appendTo(navRow).on('click', function () {
            if (currentPage > 0) {
              currentPage--;
              //renderYears();
            }
          });
          $('<th>').attr('colspan', 5).addClass('datepicker-switch').appendTo(navRow);
          $('<th>').addClass('nextcustom').attr('tabindex', 0).text('»').appendTo(navRow).on('click', function () {
            if (currentPage < totalPages - 1) {
              currentPage++;
              //renderYears();
            }
          });
        }else{
          $('<th>').attr('colspan', 5).addClass('datepicker-switch').appendTo(navRow);
        }
        
          
      function renderYears() {
        var start = currentPage * yearsPerPage;
        var end = Math.min(start + yearsPerPage, availableYears.length);
        var displayedYears = availableYears.slice(start, end);

        picker.find('.datepicker-switch').text(displayedYears[0] + ' - ' + displayedYears[displayedYears.length - 1]).css({
          'pointer-events': 'none',
          'cursor': 'default',
          'background-color': 'transparent'
        }).on('click', function (event) {
          event.stopPropagation();
        });

        picker.find('tbody').remove();
        var tbody = $('<tbody>').appendTo(picker);
        var row = $('<tr>').appendTo(tbody);
        var cell = $('<td>').attr('colspan', 7).appendTo(row);
        var activeYear = $("#edit-field-date-request-received-value").val();

        $.each(displayedYears, function (index, year) {
          $('<span>')
            .addClass('year')
            .attr('tabindex', -1)
            .text(year)
            .appendTo(cell)
            .on('keydown', function (event) {
              //handleKeyboardNavigation(event, $(this), displayedYears);
            });
        });
        if(activeYear){
         $('.datepicker-years tbody tr td').find('span').each(function(){
            if($(this).html() == activeYear){
              $(this).addClass('active');
            }
         })
       }
        $('th.prevcustom').focus();
      } 
      
      function handleKeyboardNavigation(event, element, displayedYears) {
        var key = event.which || event.keyCode;
        var currentIndex = displayedYears.indexOf(parseInt(element.text()));
        if (key === 37) {
          var prevElement = element.closest('span').prev().find('.year').last();
          if (prevElement.length) {
            prevElement.focus();
          } else {
            element.prev().focus(); // Fallback if elements are direct siblings
          }
        } else if (key === 39 && currentIndex < displayedYears.length - 1) {
          element.next().focus();
        } else if (key === 9) {
          event.preventDefault(); 
          var isYear = element.hasClass('year');
          if(isYear){
            $('.js-datepicker-years-filter').datepicker('hide');
            if (event.shiftKey) {
              setTimeout(function () {
                $('#edit-combine').focus();
              }, 0);
            } else {
              setTimeout(function () {
                $('#edit-field-lga-target-id').focus();
              }, 0);
            }
          }
        } else if (key === 13) {
          element.click();
        }
      }
      renderYears();   
      picker.find('.prevcustom, .nextcustom, .year:first').on('keydown', function (event) {
        var key = event.which || event.keyCode;
      
        if (key === 13 || key === 32) {
          // Enter or Space triggers click
          $(this).click();
        } else if (key === 9) {
          event.preventDefault(); // Prevent default Tab behavior
      
          if (event.shiftKey) {
            if ($(this).hasClass('year')) {
              picker.find('.nextcustom').focus();
            } else if ($(this).hasClass('nextcustom')) {
              picker.find('.prevcustom').focus();
            } else if ($(this).hasClass('prevcustom')) {
              $('.js-datepicker-years-filter').datepicker('hide');
              setTimeout(function () {
                $('#edit-combine').focus();
              }, 0);
            }
          } else {
            if ($(this).hasClass('prevcustom')) {
              picker.find('.nextcustom').focus();
              
            } else if ($(this).hasClass('year')) {
              $('.js-datepicker-years-filter').datepicker('hide');
              setTimeout(function () {
                $('#edit-field-lga-target-id').focus();
              }, 0);
            } else if ($(this).hasClass('nextcustom')) {
              picker.find('.year:first').focus();
      
            }
          }
        }
      });


    });
      
    },
  };
})(jQuery, Drupal);
