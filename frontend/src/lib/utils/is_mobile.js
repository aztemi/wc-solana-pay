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
