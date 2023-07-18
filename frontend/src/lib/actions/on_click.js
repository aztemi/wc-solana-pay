/**
 * @param {HTMLElement} node
 * @param {(arg?: any) => void} callback
 */
export function onClick(node, callback) {
  node.addEventListener("click", handleClick, true);

  /**
   * @param {MouseEvent} event
   */
  function handleClick(event) {
    if (!event.defaultPrevented) {
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
      node.removeEventListener("click", handleClick, true);
    }
  };
}
