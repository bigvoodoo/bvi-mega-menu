(function () {
  'use strict';

  const SELECTORS = {
    block: '.bvi-mega-menu',
    hamburger: '.bvi-mega-menu-hamburger',
    item: '.bvi-menu-item',
    itemHasPanel: '.bvi-menu-item.has-panel',
    itemLink: '.bvi-menu-item-link',
    itemToggle: '.bvi-menu-item-toggle',
    panel: '.bvi-mega-panel',
  };

  const CLASSES = {
    open: 'is-open',
    itemOpen: 'is-open',
    mobile: 'is-mobile',
    bodyLock: 'bvi-mega-menu-body-lock',
  };

  class MegaMenuBlock {
    /** @type {HTMLElement} */
    nav;

    /** @type {number} */
    breakpoint;

    /** @type {string} */
    mobileMode;

    /** @type {string} */
    trigger;

    /** @type {number} */
    closeDelay;

    /** @type {boolean} */
    spanParent;

    /** @type {string} */
    mobileDropdownAlignment;

    /** @type {MediaQueryList} */
    mql;

    /** @type {Map<HTMLElement, number>} */
    closeTimers;

    /**
     * Constructor.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} nav Block wrapper element.
     * @return {void}
     */
    constructor(nav) {
      this.nav = nav;
      this.closeTimers = new Map();
    }

    /**
     * Read config from data attributes, bind all events, and set initial state.
     *
     * @since 5.0.0
     *
     * @return {void}
     */
    init() {
      this.breakpoint = parseInt(this.nav.dataset.mobileBreakpoint || '960', 10);
      this.mobileMode = this.nav.dataset.mobileMode || 'none';
      this.trigger = this.nav.dataset.dropdownTrigger === 'click' ? 'click' : 'hover';
      this.closeDelay = Math.max(0, parseInt(this.nav.dataset.closeDelay || '300', 10));
      this.mobileLevels = parseInt(this.nav.dataset.mobileLevels || '1', 10);
      this.spanParent = this.nav.dataset.dropdownSpanParent === 'true';
      this.mobileDropdownAlignment = this.nav.dataset.mobileDropdownAlignment || 'viewport';
      this.mql = window.matchMedia(`(max-width: ${this.breakpoint - 1}px)`);

      window.requestAnimationFrame(() => this.updatePositionVars());
      window.addEventListener('load', () => this.updatePositionVars());
      window.addEventListener('resize', () => this.updatePositionVars());

      this.bindHamburger();
      this.bindOverlay();
      this.bindDesktopHandlers();
      this.bindClickDelegate();
      this.bindOutsideClick();
      this.bindEscKey();

      const onBreakpoint = () => this.handleBreakpoint();
      this.handleBreakpoint();

      if (typeof this.mql.addEventListener === 'function') {
        this.mql.addEventListener('change', onBreakpoint);
      } else if (typeof this.mql.addListener === 'function') {
        this.mql.addListener(onBreakpoint);
      }
    }

    /**
     * Check whether the menu is currently in mobile mode.
     *
     * @since 5.0.0
     *
     * @return {boolean} True when mobile mode is active and the breakpoint matches.
     */
    isMobile() {
      return this.mobileMode !== 'none' && this.mql.matches;
    }

    /**
     * Measure the nav's position and update CSS custom properties for panel alignment.
     *
     * Sets `--bvi-mm-span-left` / `--bvi-mm-span-width` for the span-parent feature
     * and `--bvi-mm-mobile-vp-left` for viewport-aligned mobile dropdowns.
     *
     * @since 5.0.0
     *
     * @return {void}
     */
    updatePositionVars() {
      if (this.spanParent) {
        const parent = this.nav.parentElement;
        if (parent) {
          const navRect = this.nav.getBoundingClientRect();
          const parentRect = parent.getBoundingClientRect();
          this.nav.style.setProperty('--bvi-mm-span-left', '-' + (navRect.left - parentRect.left) + 'px');
          this.nav.style.setProperty('--bvi-mm-span-width', parentRect.width + 'px');
        }
      }
      if (this.mobileDropdownAlignment === 'viewport') {
        const navRect = this.nav.getBoundingClientRect();
        this.nav.style.setProperty('--bvi-mm-mobile-vp-left', '-' + navRect.left + 'px');
      }
    }

    /**
     * Clear any pending auto-close timer for a menu item.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} item Menu item element.
     * @return {void}
     */
    clearCloseTimer(item) {
      const id = this.closeTimers.get(item);
      if (id) {
        clearTimeout(id);
        this.closeTimers.delete(item);
      }
    }

    /**
     * Count how many .bvi-menu-item ancestors an item has up to the nav.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} item Menu item element.
     * @return {number} Nesting depth (0 = top-level).
     */
    getItemDepth(item) {
      let depth = 0;
      let el = item.parentElement;
      while (el && el !== this.nav) {
        if (el.classList.contains('bvi-menu-item')) {
          depth++;
        }
        el = el.parentElement;
      }
      return depth;
    }

    /**
     * Whether a menu item may expand its panel in mobile mode.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} item Menu item element.
     * @return {boolean} True when the item may expand its panel in mobile mode.
     */
    canExpandInMobile(item) {
      if (this.mobileLevels === 0) {
        return true;
      }

      return this.getItemDepth(item) < this.mobileLevels - 1;
    }

    /**
     * Schedule an auto-close for an item after the configured delay.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} item Menu item element.
     * @return {void}
     */
    scheduleClose(item) {
      this.clearCloseTimer(item);
      if (this.closeDelay <= 0) {
        return;
      }
      const id = setTimeout(() => {
        MegaMenuBlock.closeItem(item);
        this.closeTimers.delete(item);
      }, this.closeDelay);
      this.closeTimers.set(item, id);
    }

    /**
     * Open an item and start the auto-close countdown in click mode.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} item Menu item element.
     * @return {void}
     */
    openWithTimer(item) {
      MegaMenuBlock.openItem(item);

      if (this.trigger === 'click' && !this.isMobile()) {
        this.scheduleClose(item);
      } else {
        this.clearCloseTimer(item);
      }
    }

    /**
     * Wire up the hamburger toggle button.
     *
     * @since 5.0.0
     *
     * @return {void}
     */
    bindHamburger() {
      const hamburger = this.nav.querySelector(SELECTORS.hamburger);
      if (!hamburger) {
        return;
      }
      hamburger.addEventListener('click', (e) => {
        e.preventDefault();
        if (this.nav.classList.contains(CLASSES.open)) {
          MegaMenuBlock.closeHamburger(this.nav);
          MegaMenuBlock.closeAllItems(this.nav);
        } else {
          MegaMenuBlock.openHamburger(this.nav);
        }
      });
    }

    /**
     * Wire up the overlay click-to-close in popup mode.
     *
     * @since 5.0.0
     *
     * @return {void}
     */
    bindOverlay() {
      const overlay = this.nav.querySelector('.bvi-mega-menu-overlay');
      if (!overlay) {
        return;
      }
      overlay.addEventListener('click', () => {
        MegaMenuBlock.closeHamburger(this.nav);
        MegaMenuBlock.closeAllItems(this.nav);
      });
    }

    /**
     * Attach mouseenter, mouseleave, and focus handlers to all items with panels.
     *
     * In hover mode: mouseenter opens the panel, mouseleave schedules the auto-close.
     * In click mode: hover keeps an already-open panel alive while the user is over it.
     *
     * @since 5.0.0
     *
     * @return {void}
     */
    bindDesktopHandlers() {
      this.nav.querySelectorAll(SELECTORS.itemHasPanel).forEach((item) => {
        item.addEventListener('mouseenter', () => {
          if (this.isMobile()) {
            return;
          }
          if (this.trigger === 'hover') {
            this.clearCloseTimer(item);
            MegaMenuBlock.openItem(item);
          } else if (item.classList.contains(CLASSES.itemOpen)) {
            this.clearCloseTimer(item);
          }
        });

        item.addEventListener('mouseleave', () => {
          if (this.isMobile()) {
            return;
          }
          if (this.trigger === 'hover' || item.classList.contains(CLASSES.itemOpen)) {
            this.scheduleClose(item);
          }
        });

        item.addEventListener('focusin', () => {
          if (this.isMobile() || this.trigger !== 'hover') {
            return;
          }
          this.clearCloseTimer(item);
          MegaMenuBlock.openItem(item);
        });

        item.addEventListener('focusout', (event) => {
          if (this.isMobile() || this.trigger !== 'hover') {
            return;
          }

          if (item.contains(event.relatedTarget)) {
            return;
          }
          this.scheduleClose(item);
        });
      });
    }

    /**
     * Delegate click events on item links and toggle buttons inside the nav.
     *
     * @since 5.0.0
     *
     * @return {void}
     */
    bindClickDelegate() {
      this.nav.addEventListener('click', (event) => {
        const toggleBtn = event.target.closest(SELECTORS.itemToggle);
        if (toggleBtn && this.nav.contains(toggleBtn)) {
          const item = toggleBtn.closest(SELECTORS.itemHasPanel);
          if (item) {
            if (this.isMobile() && !this.canExpandInMobile(item)) {
              return;
            }
            event.preventDefault();
            if (item.classList.contains(CLASSES.itemOpen)) {
              this.clearCloseTimer(item);
              MegaMenuBlock.closeItem(item);
            } else {
              this.openWithTimer(item);
            }
            return;
          }
        }

        const link = event.target.closest(SELECTORS.itemLink);
        if (!link || !this.nav.contains(link)) {
          return;
        }

        const item = link.closest(SELECTORS.itemHasPanel);
        if (!item) {
          return;
        }

        if (link.tagName === 'BUTTON') {
          if (this.isMobile() && !this.canExpandInMobile(item)) {
            return;
          }
          event.preventDefault();
          if (item.classList.contains(CLASSES.itemOpen)) {
            this.clearCloseTimer(item);
            MegaMenuBlock.closeItem(item);
          } else {
            this.openWithTimer(item);
          }
          return;
        }

        if (this.isMobile() && !item.classList.contains(CLASSES.itemOpen) && this.canExpandInMobile(item)) {
          event.preventDefault();
          MegaMenuBlock.openItem(item);
          return;
        }

        if (this.trigger === 'click' && !this.isMobile()) {
          if (!item.classList.contains(CLASSES.itemOpen)) {
            event.preventDefault();
            this.openWithTimer(item);
          }
        }
      });
    }

    /**
     * Close all items and the hamburger when a click lands outside the nav.
     *
     * @since 5.0.0
     *
     * @return {void}
     */
    bindOutsideClick() {
      document.addEventListener('click', (event) => {
        if (this.nav.contains(event.target)) {
          return;
        }
        if (this.nav.classList.contains(CLASSES.open)) {
          MegaMenuBlock.closeHamburger(this.nav);
        }
        this.nav
          .querySelectorAll(SELECTORS.itemHasPanel + '.' + CLASSES.itemOpen)
          .forEach((item) => this.clearCloseTimer(item));
        MegaMenuBlock.closeAllItems(this.nav);
      });
    }

    /**
     * Close the open menu when the Escape key is pressed.
     *
     * @since 5.0.0
     *
     * @return {void}
     */
    bindEscKey() {
      document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
          return;
        }
        if (this.nav.classList.contains(CLASSES.open)) {
          MegaMenuBlock.closeHamburger(this.nav);
          const hamburgerEl = this.nav.querySelector(SELECTORS.hamburger);
          if (hamburgerEl) {
            hamburgerEl.focus();
          }
        }
        this.nav
          .querySelectorAll(SELECTORS.itemHasPanel + '.' + CLASSES.itemOpen)
          .forEach((item) => this.clearCloseTimer(item));
        MegaMenuBlock.closeAllItems(this.nav);
      });
    }

    /**
     * Sync the `.is-mobile` class and reset state when the breakpoint changes.
     *
     * @since 5.0.0
     *
     * @return {void}
     */
    handleBreakpoint() {
      const mobile = this.isMobile();
      this.nav.classList.toggle(CLASSES.mobile, mobile);

      this.nav.querySelectorAll(SELECTORS.itemHasPanel).forEach((item) => {
        const toggle = item.querySelector(':scope > ' + SELECTORS.itemToggle);
        if (!toggle) {
          return;
        }
        if (mobile && !this.canExpandInMobile(item)) {
          toggle.setAttribute('hidden', '');
        } else {
          toggle.removeAttribute('hidden');
        }
      });

      if (!mobile) {
        MegaMenuBlock.closeHamburger(this.nav);
        MegaMenuBlock.closeAllItems(this.nav);
      }

      this.closeTimers.forEach((id) => clearTimeout(id));
      this.closeTimers.clear();
    }

    /**
     * Set aria-expanded on the item's link and toggle controls to reflect open state.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} item Menu item element.
     * @param {boolean}     open Whether the panel is open.
     * @return {void}
     */
    static setItemAriaExpanded(item, open) {
      const link = item.querySelector(':scope > ' + SELECTORS.itemLink);
      const toggle = item.querySelector(':scope > ' + SELECTORS.itemToggle);
      if (link && link.hasAttribute('aria-expanded')) {
        link.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
      if (toggle) {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
    }

    /**
     * Close every open menu-item panel within the given nav.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} nav Block wrapper element.
     * @return {void}
     */
    static closeAllItems(nav) {
      nav.querySelectorAll(SELECTORS.itemHasPanel + '.' + CLASSES.itemOpen).forEach((item) => {
        item.classList.remove(CLASSES.itemOpen);
        MegaMenuBlock.setItemAriaExpanded(item, false);
      });
    }

    /**
     * Close all open sibling items of the given item at the same nesting level.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} item Menu item element.
     * @return {void}
     */
    static closeSiblings(item) {
      const parent = item.parentElement;
      if (!parent) {
        return;
      }
      parent.querySelectorAll(':scope > ' + SELECTORS.itemHasPanel + '.' + CLASSES.itemOpen).forEach((sibling) => {
        if (sibling !== item) {
          sibling.classList.remove(CLASSES.itemOpen);
          MegaMenuBlock.setItemAriaExpanded(sibling, false);
        }
      });
    }

    /**
     * Open a menu item's panel.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} item Menu item element.
     * @return {void}
     */
    static openItem(item) {
      MegaMenuBlock.closeSiblings(item);
      item.classList.add(CLASSES.itemOpen);
      MegaMenuBlock.setItemAriaExpanded(item, true);
    }

    /**
     * Close a menu item's panel.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} item Menu item element.
     * @return {void}
     */
    static closeItem(item) {
      if (!item.classList.contains(CLASSES.itemOpen)) {
        return;
      }
      item.classList.remove(CLASSES.itemOpen);
      MegaMenuBlock.setItemAriaExpanded(item, false);
    }

    /**
     * Close the hamburger mobile menu.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} nav Block wrapper element.
     * @return {void}
     */
    static closeHamburger(nav) {
      nav.classList.remove(CLASSES.open);
      const hamburger = nav.querySelector(SELECTORS.hamburger);
      if (hamburger) {
        hamburger.setAttribute('aria-expanded', 'false');
      }
      document.body.classList.remove(CLASSES.bodyLock);
    }

    /**
     * Open the hamburger mobile menu.
     *
     * @since 5.0.0
     *
     * @param {HTMLElement} nav Block wrapper element.
     * @return {void}
     */
    static openHamburger(nav) {
      nav.classList.add(CLASSES.open);
      const hamburger = nav.querySelector(SELECTORS.hamburger);
      if (hamburger) {
        hamburger.setAttribute('aria-expanded', 'true');
      }
      if (nav.classList.contains('bvi-mega-menu-mobile-popup')) {
        document.body.classList.add(CLASSES.bodyLock);
      }
    }
  }

  // find all potential mega menus and activate their functionality
  function initMegaMenus() {
    document.querySelectorAll(SELECTORS.block).forEach((nav) => {
      new MegaMenuBlock(nav).init();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMegaMenus);
  } else {
    initMegaMenus();
  }
})();
