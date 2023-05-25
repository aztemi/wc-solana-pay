export function clickOutside(node, callback) {
  document.addEventListener("click", handleClick, true);

  function handleClick(event) {
    if (node && !node.contains(event.target) && !event.defaultPrevented) {
      callback();
      event.preventDefault();
      event.stopPropagation();
    }
  }

  return {
    update(newCallback) {
      callback = newCallback;
    },
    destroy() {
      document.removeEventListener("click", handleClick, true);
    }
  };
}
