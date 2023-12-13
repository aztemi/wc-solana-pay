import { writable } from "svelte/store";
import { isLocalhost } from "../../utils/helpers";

const TIMEOUT_DELAY = 5000;

/**
 * @typedef {(typeof STATE)[keyof typeof STATE]} StateType
 * @typedef {(typeof EXIT)[keyof typeof EXIT]} ExitType
 */

export const STATE = {
  NONE: 1,
  LOADING: 2,
  ERROR: 3,
  OK: 4
};

export const EXIT = {
  NONE: 1,
  TIMEOUT: 2,
  MANUAL: 3
};

export const notification = createNotificationStore();

function createNotificationStore() {
  const { subscribe, set, update } = writable({ count: 0, notices: [] });

  /**
   * @param {number} id
   * @param {ExitType} exit
   */
  function timeout(id, exit) {
    if (id > 0 && exit === EXIT.TIMEOUT) setTimeout(() => notification?.removeNotice(id), TIMEOUT_DELAY);
  }

  return {
    subscribe,

    reset: () => set({ count: 0, notices: [] }),

    /**
     * @param {string} message
     */
    addNotice: (message, status = STATE.NONE, exit = EXIT.NONE) => {
      let id = -1;
      update(last => {
        let { count, notices } = last;
        id = ++count;
        notices.push({ id, message, status, exit });

        return { ...last, count, notices };
      });
      timeout(id, exit);

      return id;
    },

    /**
     * @param {number} id
     * @param {{message?:string, status?: StateType, exit?: ExitType, error?:string}} content
     */
    updateNotice: (id, content) =>
      update(last => {
        const { notices } = last;
        const idx = notices.findIndex(n => n.id === id);
        if (idx > -1) notices[idx] = { ...notices[idx], ...content };
        timeout(id, content?.exit);

        return { ...last, notices };
      }),

    /**
     * @param {number} id
     */
    removeNotice: id =>
      update(last => {
        const { notices } = last;
        const idx = notices.findIndex(n => n.id === id);
        if (idx > -1) notices.splice(idx, 1);

        return { ...last, notices };
      })
  };
}

export const showSubmitOrderStatus = (function () {
  let msgId = -1;

  return function () {
    if (msgId >= 0) return;
    msgId = notification.addNotice("Submitting order", STATE.LOADING);
    if (isLocalhost())
      notification.updateNotice(msgId, {
        status: STATE.ERROR,
        exit: EXIT.MANUAL,
        error: "WordPress is on localhost. Webhook callback not available."
      });
  };
})();
