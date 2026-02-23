import './source.scss'

Drupal.behaviors.cameraIconPopupSource = {
  attach: function () {
    const popUpEventTypes = ["keypress", "click"];
    once("popup-source-icon", ".popup-source-icon").forEach((element) => {
      if (!element.classList.contains("processed")) {
        managePopupEventHandlers(element, popUpEventTypes, showSourceInfo);
      }
      element.classList.add("processed");
    });

    once("popup-source-close", ".popup-source-close").forEach((element) => {
      if (!element.classList.contains("processed")) {
        managePopupEventHandlers(element, popUpEventTypes, closeSourceInfo);
      }
      element.classList.add("processed");
    });

    function getSourceElements(target) {
      const parent = target.closest(".popup-source");
      return {
        parent,
        icon: parent.querySelector(".popup-source-icon"),
        content: parent.querySelector(".popup-source-text"),
      };
    }

    function showSourceInfo(evt) {
      evt.stopImmediatePropagation();
      evt.preventDefault();
      if (evt.type === "keypress" && ![" ", "Enter"].includes(evt.key)) {
        return;
      }
      const { icon, content } = getSourceElements(
        getTarget(evt.target, "button")
      );
      content.classList.remove("nsw-hide-xs");
      icon.classList.add("nsw-hide-xs");
      // setSourcePopupAttributes(icon, content, true)
      content.querySelector('[data-type="close"]').focus();
    }

    function closeSourceInfo(evt) {
      evt.stopImmediatePropagation();
      evt.preventDefault();
      if (evt.type === "keypress" && ![" ", "Enter"].includes(evt.key)) {
        return;
      }
      const { icon, content } = getSourceElements(
        getTarget(evt.target, "button")
      );
      icon.classList.remove("nsw-hide-xs");
      content.classList.add("nsw-hide-xs");
      // setSourcePopupAttributes(icon, content, false)
      icon.focus();
    }

    function managePopupEventHandlers(element, events, handler) {
      events.forEach((evt) => {
        element.removeEventListener(evt, handler);
        element.addEventListener(evt, handler);
      });
    }

    function getTarget(el, type) {
      if (el.nodeName !== type.toUpperCase()) {
        return el.closest(type);
      }
      return el;
    }

  }
}
