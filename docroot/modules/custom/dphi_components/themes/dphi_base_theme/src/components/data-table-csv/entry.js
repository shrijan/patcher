import './data-table-csv.scss'

import Dialog from 'nsw-design-system/src/components/dialog/dialog';
import DataTable from 'datatables.net-dt'

Drupal.behaviors.dataTableEntry = {
  attach: () => {
    once('dataTableEntry', '.tab-data-table').forEach(element => {
      const loadModals = () => {
        element.closest('.cl--data-table').parentNode.querySelectorAll('.nsw-dialog').forEach(dialogElement => {
          const dialog = new Dialog(dialogElement)
          dialog.init()

          dialog.focusableEls[0].addEventListener('blur', event => {
            if (!dialog.element.contains(event.relatedTarget)) {
              dialog.focusableEls[dialog.focusableEls.length - 1].focus()
            }
          })

          dialog.openBtn.forEach((btn) => {
            btn.addEventListener('keydown', event => {
              if (event.key == 'Enter') {
                // Temporarily disable closing through the keyboard
                dialog.closeBtn.forEach((btn) => {
                  btn.removeEventListener('click', dialog.closeEvent)
                })
                document.addEventListener('keyup', () => {
                  // And re-enable it, once the original key has been lifted
                  dialog.closeBtn.forEach((btn) => {
                    btn.addEventListener('click', dialog.closeEvent, false)
                  })
                }, {once: true})
              }
            })
          })

          dialog.elementWrapper.addEventListener('keydown', event => {
            if (event.key == 'Escape') {
              dialog.closeDialog()
            }
          })
        })
      }
      loadModals()

      const filterField1 =  Math.min(element.dataset.filter1index, 3)
      const filter2Col = element.dataset.filter2index
      let filterField2 = filter2Col > 2 ? 4 : filter2Col
      if (filterField1 <= 2 && filterField2 > 2) {
        filterField2 = 3;
      }
      const status1 = element.querySelector('.h1-active')
      const status2 = element.querySelector('.h2-active')
      let cols
      const obj1 = {
        orderable: false,
        targets: 3,
        visible: false
      }
      if ((status1 && !status2) || (status2 && !status1)) {
        cols = [
          obj1,
          {
            orderable: false,
            targets: 4,
            visible: true
          }
        ]
      } else if (status1 && status2) {
        cols = [
          obj1,
          {
            orderable: false,
            targets: 4,
            visible: false
          },
          {
            orderable: false,
            targets: 5,
            visible: true
          }
        ]
      } else {
        cols = [
          {
            orderable: false,
            targets: 3,
            visible: true
          }
        ]
      }
      const dt = new DataTable(element, {
        dom: `lf<".dataTables_tableWrapper"t>ip`, // no filters showing up here.
        pagingType: 'simple_numbers',
        language: {
          paginate: {
            first: "First",
            previous: "Previous",
            next: "Next",
            last: "Last",
            aria: {
              sortAscending: " - click/return to sort ascending",
              sortDescending: " - click/return to sort descending",
            }
          }
        },
        columnDefs: cols,
        initComplete: function (settings, json) {
          const dt = element.closest('.dt-container').querySelector('.dt-search')

          const searchDiv = document.createElement('div')
          searchDiv.setAttribute('role', 'search')
          dt.appendChild(searchDiv)

          searchDiv.appendChild(dt.querySelector('label'))

          const searchInput = dt.querySelector('input')
          searchInput.classList.add('nsw-form__input')
          searchDiv.appendChild(searchInput)

          this.api()
            .columns([filterField1, filterField2])
            .every(function (colIndex) {
              const column = this
              const filter1 = element.dataset.filter1name
              const filter2 = element.dataset.filter2name
              const caption_id = colIndex == filterField1 ? filter1 : filter2

              const label = document.createElement('label')
              label.innerText = 'Filter by:'
              dt.appendChild(label)

              const pagination = element.querySelectorAll('.dt-paging > span a')
              if (pagination.length > 1) {
                // gets the content of the final page to match against
                const lastPage = pagination[pagination.length-1].innerText
                pagination.forEach(page => {
                  page.setAttribute('aria-label', 'go to page '+page.innerText+' of '+lastPage)
                })
              }
              const select = document.createElement('select')
              select.classList.add('nsw-form__select')
              select.dataset.rel = caption_id
              const option = document.createElement('option')
              option.value = ''
              option.innerText = colIndex == filterField1 ? "All "+filter1 : "All "+filter2
              select.appendChild(option)
              label.appendChild(select)
              select.addEventListener('change', () => {
                const val = DataTable.util.escapeRegex(select.value)
                column.search(val ? ("^" + val + "$") : "", true, false).draw()
              })
              column
                .data()
                .unique()
                .sort()
                .each(d => {
                  const option = document.createElement('option')
                  option.value = d
                  option.innerText = d
                  select.appendChild(option)
                })
            })
        },
      })
      dt.on("draw", loadModals)
    })
  }
}
