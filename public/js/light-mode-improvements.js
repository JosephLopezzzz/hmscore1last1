/**
 * Light Mode Improvements for Inn Nexus Hotel Management System
 * Provides consistent light mode styling across all pages
 */

(function() {
  'use strict';

  // Initialize light mode improvements
  function initLightMode() {
    // Add light-mode class to document element
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.classList.toggle('light-mode', theme === 'light');
    
    // Apply light mode improvements to existing elements
    applyLightModeImprovements();
    
    // Listen for theme changes
    window.addEventListener('storage', function(e) {
      if (e.key === 'theme') {
        document.documentElement.classList.toggle('light-mode', e.newValue === 'light');
        applyLightModeImprovements();
      }
    });
  }

  // Apply light mode improvements to elements
  function applyLightModeImprovements() {
    // Improve card shadows and borders
    const cards = document.querySelectorAll('.bg-card, .room-card, .task-card');
    cards.forEach(card => {
      if (document.documentElement.classList.contains('light-mode')) {
        card.style.boxShadow = '0 1px 2px 0 rgb(0 0 0 / 0.05)';
        card.style.border = '1px solid hsl(var(--border))';
      }
    });

    // Improve button contrast
    const buttons = document.querySelectorAll('button:not([class*="bg-"])');
    buttons.forEach(button => {
      if (document.documentElement.classList.contains('light-mode')) {
        button.style.backgroundColor = 'hsl(var(--card))';
        button.style.color = 'hsl(var(--card-foreground))';
        button.style.borderColor = 'hsl(var(--border))';
      }
    });

    // Improve input field styling
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
      if (document.documentElement.classList.contains('light-mode')) {
        input.style.backgroundColor = 'hsl(var(--background))';
        input.style.color = 'hsl(var(--foreground))';
        input.style.borderColor = 'hsl(var(--border))';
      }
    });

    // Improve scrollbar styling
    const scrollableElements = document.querySelectorAll('.status-content, .overflow-auto');
    scrollableElements.forEach(element => {
      if (document.documentElement.classList.contains('light-mode')) {
        element.style.scrollbarWidth = 'thin';
        element.style.scrollbarColor = 'hsl(var(--primary)) hsl(var(--muted))';
      }
    });
  }

  // Enhanced hover effects for light mode
  function enhanceHoverEffects() {
    const interactiveElements = document.querySelectorAll('.room-card, .task-card, .card');
    
    interactiveElements.forEach(element => {
      element.addEventListener('mouseenter', function() {
        if (document.documentElement.classList.contains('light-mode')) {
          this.style.transform = 'translateY(-2px)';
          this.style.boxShadow = '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)';
        }
      });
      
      element.addEventListener('mouseleave', function() {
        if (document.documentElement.classList.contains('light-mode')) {
          this.style.transform = 'translateY(0)';
          this.style.boxShadow = '0 1px 2px 0 rgb(0 0 0 / 0.05)';
        }
      });
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      initLightMode();
      enhanceHoverEffects();
    });
  } else {
    initLightMode();
    enhanceHoverEffects();
  }

  // Re-apply improvements when new content is added dynamically
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
        setTimeout(applyLightModeImprovements, 100);
        setTimeout(enhanceHoverEffects, 100);
      }
    });
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true
  });

})();
