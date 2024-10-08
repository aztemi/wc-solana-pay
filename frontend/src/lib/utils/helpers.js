export function isLocalhost() {
  return location.hostname === "localhost" || location.hostname === "127.0.0.1";
}

// Check if the client is a mobile device or not
// This code was taken from https://github.com/WalletConnect/web3modal/blob/bb797372281816378f241093171a12a9f8d6e9fc/packages/core/src/utils/CoreUtil.ts
export function isMobile() {
  if (typeof window !== "undefined") {
    return Boolean(
      window.matchMedia("(pointer:coarse)").matches ||
        /Android|webOS|iPhone|iPad|iPod|BlackBerry|Opera Mini/u.test(navigator.userAgent)
    );
  }

  return false;
}

// safely decode HTML entities
export function decodeEntities(encodedStr) {
  const textArea = document.createElement("textarea");
  textArea.innerHTML = encodedStr;
  return textArea.value;
}
