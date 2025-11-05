(function (Drupal, once) {
  Drupal.behaviors.ensureParentCheckboxChecked = {
    attach: function (context, settings) {
      // Use `once` to ensure event listeners are only attached once.
      const checkboxes = once('ensure-parent-checkbox-checked', '.field--name-field-choose-available-filters input[type="checkbox"]', context);

      checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
          const currentCheckbox = this;
          const parentDiv = currentCheckbox.closest('.form-checkboxes');

          if (!parentDiv) return;

          // Find the parent of the current checkbox, if it is a child.
          const parentCheckbox = findParentCheckbox(currentCheckbox, parentDiv);

          if (parentCheckbox) {
            if (currentCheckbox.checked) {
              // If the child is checked, check the parent.
              parentCheckbox.checked = true;
            } else {
              // If the child is unchecked, check if any other children under the same parent are checked.
              const otherChildrenChecked = findSiblingChildren(currentCheckbox, parentDiv, parentCheckbox).some(sibling => sibling.checked);

              // Uncheck the parent only if no other children are checked.
              if (!otherChildrenChecked) {
                parentCheckbox.checked = false;
              }
            }
          }

          // Handle unchecking of a parent checkbox.
          if (!parentCheckbox && !currentCheckbox.checked) {
            const children = findChildren(currentCheckbox, parentDiv);
            children.forEach(function (childCheckbox) {
              childCheckbox.checked = false;
            });
          }
        });
      });

      /**
       * Helper function to find the parent checkbox of a given child checkbox.
       */
      function findParentCheckbox(childCheckbox, parentDiv) {
        let childLabel = childCheckbox.labels[0].innerText;

        // If the checkbox is a parent (no leading hyphen), it's the top of its own hierarchy.
        if (!childLabel.startsWith('-')) {
          return null;
        }

        // Traverse backward to find the first non-child checkbox within the same fieldset.
        const allSiblings = Array.from(parentDiv.querySelectorAll('input[type="checkbox"]'));
        for (let i = allSiblings.indexOf(childCheckbox) - 1; i >= 0; i--) {
          const siblingLabel = allSiblings[i].labels[0].innerText;
          if (!siblingLabel.startsWith('-')) {
            // Ensure that this sibling is within the same fieldset group.
            if (isSiblingWithinSameGroup(childCheckbox, allSiblings[i])) {
              return allSiblings[i];
            }
          }
        }

        return null;
      }

      /**
       * Helper function to check if two checkboxes are within the same group.
       */
      function isSiblingWithinSameGroup(childCheckbox, siblingCheckbox) {
        const childFieldset = childCheckbox.closest('fieldset');
        const siblingFieldset = siblingCheckbox.closest('fieldset');
        return childFieldset && siblingFieldset && childFieldset === siblingFieldset;
      }

      /**
       * Helper function to find all children of a given parent checkbox.
       */
      function findChildren(parentCheckbox, parentDiv) {
        const allCheckboxes = Array.from(parentDiv.querySelectorAll('input[type="checkbox"]'));
        const parentIndex = allCheckboxes.indexOf(parentCheckbox);

        const children = [];
        for (let i = parentIndex + 1; i < allCheckboxes.length; i++) {
          const label = allCheckboxes[i].labels[0].innerText;
          if (label.startsWith('-')) {
            if (isSiblingWithinSameGroup(parentCheckbox, allCheckboxes[i])) {
              children.push(allCheckboxes[i]);
            }
          } else {
            break;  // Stop when reaching the next parent-level checkbox
          }
        }
        return children;
      }

      /**
       * Helper function to find sibling children of a child checkbox under the same parent.
       */
      function findSiblingChildren(childCheckbox, parentDiv, parentCheckbox) {
        const allChildren = findChildren(parentCheckbox, parentDiv);
        return allChildren.filter(sibling => sibling !== childCheckbox);
      }
    }
  };
})(Drupal, once);
