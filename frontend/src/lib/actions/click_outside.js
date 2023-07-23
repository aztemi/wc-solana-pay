/**
 * @param {HTMLElement} node
 * @param {() => void} callback
 */
export function clickOutside(node, callback) {
  document.addEventListener("click", handleClick, true);

  /**
   * @param {MouseEvent} event
   */
  function handleClick(event) {
    if (node && !node.contains(event.target) && !event.defaultPrevented) {
      callback();
      event.preventDefault();
      event.stopPropagation();
    }
  }

  return {
    /**
     * @param {() => void} newCallback
     */
    update(newCallback) {
      callback = newCallback;
    },

    destroy() {
      document.removeEventListener("click", handleClick, true);
    }
  };
}
