import './publications.scss';

Drupal.behaviors.publicationview = {
  attach: function (context, settings) {
    const refillSortBy = (event) => {
      document.querySelector("#views-exposed-form-publications-page-list #edit-sort-by").value = event.target.value;
      document.querySelector("#views-exposed-form-publications-page-list #edit-submit-publications").click();
    };

    const refillFields = (event) => {
      document.querySelector("#edit-keyword").value = document.querySelector("#edit-keyword-top").value;
    }

    once('publications_sort', '.nsw-results-bar__sorting #edit-sort-by', context).forEach(element => {
      element.addEventListener('change', refillSortBy);
    });

    once('publications_keyword', '#nsw-search--in-page #edit-keyword-top', context).forEach(element => {
      element.value = document.querySelector("#edit-keyword").value;
    });

    once('publications_submit', '#views-exposed-form-publications-page-list #edit-submit-publications', context).forEach(element => {
      element.addEventListener('click', refillFields);
    });
  }
};
