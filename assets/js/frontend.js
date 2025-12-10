// Initialize GSAP ScrollTrigger
gsap.registerPlugin(ScrollTrigger);

// Default animation properties (simplified for WordPress version)
const defaultAnimationProps = {
  duration: 0.6,
  ease: 'power4'
};

// Main execution wrapped in IIFE to allow early returns
(function() {
  // Get settings from WordPress
  const settings = typeof caaSettings !== 'undefined' ? caaSettings : {};
  const logoSelector = settings.logoId || '';
  const selectedEffect = parseInt(settings.selectedEffect) || 1;
  const includedElementsStr = settings.includedElements || '';
  const excludedElementsStr = settings.excludedElements || '';
  const globalOffset = parseInt(settings.globalOffset) || 0;
  const debugMode = settings.debugMode === '1';
  
  // Parse offset settings from WordPress (allow negative values)
  const offsetStart = parseInt(settings.offsetStart) || 30;
  const offsetEnd = parseInt(settings.offsetEnd) || 10;
  
  // Parse effect mappings from Pro Version settings
  const effectMappings = Array.isArray(settings.effectMappings) ? settings.effectMappings : [];

  // Debug logging function
  const debug = {
    log: (...args) => {
      if (debugMode) {
        console.log('[CAA Debug]', ...args);
      }
    },
    warn: (...args) => {
      if (debugMode) {
        console.warn('[CAA Debug]', ...args);
      }
    },
    error: (...args) => {
      if (debugMode) {
        console.error('[CAA Debug]', ...args);
      }
    },
    group: (label) => {
      if (debugMode) {
        console.group('[CAA Debug]', label);
      }
    },
    groupEnd: () => {
      if (debugMode) {
        console.groupEnd();
      }
    }
  };

  debug.log('Plugin initialized', {
    logoSelector,
    selectedEffect,
    includedElements: includedElementsStr,
    excludedElements: excludedElementsStr,
    globalOffset,
    offsetStart,
    offsetEnd,
    debugMode
  });

  // Exit if no logo selector is provided
  if (!logoSelector) {
    console.warn('Context-Aware Animation: No logo ID specified in settings.');
    debug.warn('No logo selector provided - plugin will not run');
    return;
  }

  // Find the logo element
  const logoElement = document.querySelector(logoSelector);
  if (!logoElement) {
    console.warn(`Context-Aware Animation: Logo element not found with selector: ${logoSelector}`);
    debug.warn('Logo element not found with selector:', logoSelector);
    return;
  }

  debug.log('Logo element found:', logoElement);

  // Store original HTML content and styles for the logo element
  const originalHTMLContent = logoElement.innerHTML;
  const originalStyles = logoElement.getAttribute('style') || '';
  
  debug.log('Logo original state saved', {
    hasContent: originalHTMLContent.length > 0,
    hasStyles: originalStyles.length > 0
  });

  // Function to reset the logo element to its original state
  function resetElement(target) {
    // Restore original HTML content
    target.innerHTML = originalHTMLContent;
    // Remove any transformations and set properties back to default values
    gsap.set(target, {
      clearProps: 'all', // Clear all GSAP-applied properties
      rotation: 0,
      xPercent: 0,
      yPercent: 0,
      x: 0,
      y: 0,
      scale: 1,
      autoAlpha: 1,
    });
    // Restore any initial inline styles if present
    target.setAttribute('style', originalStyles);
  }

  // Function to get the logo element's position from the top of the viewport
  function getElementTopOffset(element) {
    const elementRect = element.getBoundingClientRect();
    return elementRect.top; // Returns the distance of the element from the top of the viewport
  }

  // Parse included and excluded elements
  const includedSelectors = includedElementsStr
    .split(',')
    .map(s => s.trim())
    .filter(s => s.length > 0);
  
  const excludedSelectors = excludedElementsStr
    .split(',')
    .map(s => s.trim())
    .filter(s => s.length > 0);

  debug.group('Element Detection');
  debug.log('Included selectors:', includedSelectors);
  debug.log('Excluded selectors:', excludedSelectors);

  // Get all content blocks
  let contentBlocks = [];
  
  // If included elements are specified, use those
  if (includedSelectors.length > 0) {
    debug.log('Using custom included selectors');
    includedSelectors.forEach(selector => {
      try {
        const elements = document.querySelectorAll(selector);
        debug.log(`Selector "${selector}" found ${elements.length} element(s)`);
        elements.forEach(el => {
          // Check if element is excluded
          const isExcluded = excludedSelectors.some(excludedSelector => {
            try {
              return el.matches(excludedSelector) || el.closest(excludedSelector) !== null;
            } catch (e) {
              return false;
            }
          });
          
          if (!isExcluded && el.offsetHeight > 0) {
            contentBlocks.push(el);
            debug.log('Added content block:', el, { selector, offsetHeight: el.offsetHeight });
          } else {
            debug.log('Skipped element:', el, { isExcluded, offsetHeight: el.offsetHeight });
          }
        });
      } catch (e) {
        console.warn(`Context-Aware Animation: Invalid selector "${selector}" in included elements.`);
        debug.error('Invalid selector:', selector, e);
      }
    });
  } else {
    debug.log('Using auto-detection for content blocks');
    // Auto-detect WordPress content blocks (common selectors)
    const contentSelectors = [
      '.entry-content',
      'main',
      '.content',
      '.post-content',
      'article',
      '.wp-block-post-content',
      '.site-content',
      '.content-area'
    ];

    contentSelectors.forEach(selector => {
      const elements = document.querySelectorAll(selector);
      debug.log(`Auto-detection selector "${selector}" found ${elements.length} element(s)`);
      elements.forEach(el => {
        // Check if element is excluded
        const isExcluded = excludedSelectors.some(excludedSelector => {
          try {
            return el.matches(excludedSelector) || el.closest(excludedSelector) !== null;
          } catch (e) {
            return false;
          }
        });
        
        if (!isExcluded && el.offsetHeight > 0) {
          contentBlocks.push(el);
          debug.log('Added content block (auto-detected):', el, { selector });
        }
      });
    });

    // If no content blocks found, try a more general approach
    if (contentBlocks.length === 0) {
      debug.log('No content blocks found with standard selectors, trying general approach');
      const bodyChildren = Array.from(document.body.children);
      contentBlocks = bodyChildren.filter(el => {
        // Skip header, nav, footer, and excluded elements
        const tagName = el.tagName.toLowerCase();
        if (['header', 'nav', 'footer', 'script', 'style'].includes(tagName)) {
          return false;
        }
        
        // Check if excluded
        const isExcluded = excludedSelectors.some(excludedSelector => {
          try {
            return el.matches(excludedSelector) || el.closest(excludedSelector) !== null;
          } catch (e) {
            return false;
          }
        });
        
        return !isExcluded && el.offsetHeight > 0;
      });
      debug.log(`General approach found ${contentBlocks.length} content block(s)`);
    }
  }

  // Remove duplicates
  contentBlocks = [...new Set(contentBlocks)];
  debug.log(`Total content blocks found: ${contentBlocks.length}`, contentBlocks);

  // Add mapped elements as content blocks (mappings work independently of Include Elements)
  if (effectMappings.length > 0) {
    debug.log('Adding mapped elements as content blocks...');
    effectMappings.forEach(mapping => {
      if (!mapping.selector) return;
      try {
        const mappedElements = document.querySelectorAll(mapping.selector.trim());
        mappedElements.forEach(el => {
          if (!contentBlocks.includes(el) && el.offsetHeight > 0) {
            contentBlocks.push(el);
            debug.log('Added mapped element as content block:', el, mapping.selector);
          }
        });
      } catch (e) {
        debug.warn('Invalid selector in mapping:', mapping.selector, e);
      }
    });
    debug.log(`Content blocks after adding mappings: ${contentBlocks.length}`, contentBlocks);
  }

  debug.groupEnd();

  // Helper function to verify SplitType library is available
  // SplitType should be loaded synchronously at page load, so this is just a verification
  function waitForSplitType() {
    // Check for SplitType on window/globalThis
    let SplitTypeLib = globalThis.SplitType || window.SplitType;
    
    // If SplitType exists but might be wrapped in a default export
    if (SplitTypeLib && typeof SplitTypeLib === 'object' && SplitTypeLib.default) {
      SplitTypeLib = SplitTypeLib.default;
    }
    
    if (SplitTypeLib && typeof SplitTypeLib === 'function') {
      return Promise.resolve(SplitTypeLib);
    }
    
    // If not available, throw error immediately (should have loaded at page load)
    throw new Error('SplitType library is not available. It should be loaded synchronously at page load. Ensure the selected effect requires text splitting and SplitType is properly enqueued.');
  }

  // Build effect functions based on effect number (allows per-element effects)
  // overrideSettings is an optional object with per-mapping settings overrides
  function buildEffect(effectNumber = selectedEffect, overrideSettings = null) {
    // Determine the source of settings - use override if provided, otherwise global
    const useOverride = overrideSettings !== null;
    
    // Merge WordPress settings with defaults, applying overrides if present
    const animationProps = {
      duration: useOverride && overrideSettings.duration !== undefined 
        ? parseFloat(overrideSettings.duration) 
        : (parseFloat(settings.duration) || defaultAnimationProps.duration),
      ease: useOverride && overrideSettings.ease !== undefined 
        ? overrideSettings.ease 
        : (settings.ease || defaultAnimationProps.ease)
    };
    
    // Use override offset settings if provided, otherwise use global WordPress settings
    const effectOffsetStart = useOverride && overrideSettings.offsetStart !== undefined 
      ? parseInt(overrideSettings.offsetStart) 
      : offsetStart;
    const effectOffsetEnd = useOverride && overrideSettings.offsetEnd !== undefined 
      ? parseInt(overrideSettings.offsetEnd) 
      : offsetEnd;
    
    debug.log('Building effect', effectNumber, 'with override:', useOverride, {
      animationProps,
      effectOffsetStart,
      effectOffsetEnd,
      overrideSettings
    });
    
    switch (effectNumber) {
      case 1: // Scale
      const effect1Settings = {
        scaleDown: useOverride && overrideSettings.effect1ScaleDown !== undefined 
          ? parseFloat(overrideSettings.effect1ScaleDown) 
          : (settings.effect1ScaleDown !== undefined && settings.effect1ScaleDown !== '' ? parseFloat(settings.effect1ScaleDown) : 0),
        originX1: useOverride && overrideSettings.effect1OriginX !== undefined 
          ? parseInt(overrideSettings.effect1OriginX) 
          : (settings.effect1OriginX !== undefined && settings.effect1OriginX !== '' ? parseInt(settings.effect1OriginX) : 0),
        originY1: useOverride && overrideSettings.effect1OriginY !== undefined 
          ? parseInt(overrideSettings.effect1OriginY) 
          : (settings.effect1OriginY !== undefined && settings.effect1OriginY !== '' ? parseInt(settings.effect1OriginY) : 50)
      };
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          gsap.set(target, { transformOrigin: `${effect1Settings.originX1}% ${effect1Settings.originY1}%` });
          target.currentTween = gsap.to(target, {
            scale: effect1Settings.scaleDown,
            autoAlpha: 0,
            ...animationProps,
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          target.currentTween = gsap.to(target, {
            scale: 1,
            autoAlpha: 1,
            ...animationProps,
            onComplete: () => {
              resetElement(target);
              target.currentTween = null;
            }
          });
        }
      };
      
      case 2: // Blur
      const effect2Settings = {
        blurAmount: useOverride && overrideSettings.effect2BlurAmount !== undefined 
          ? parseFloat(overrideSettings.effect2BlurAmount) 
          : (settings.effect2BlurAmount !== undefined && settings.effect2BlurAmount !== '' ? parseFloat(settings.effect2BlurAmount) : 5),
        blurScale: useOverride && overrideSettings.effect2BlurScale !== undefined 
          ? parseFloat(overrideSettings.effect2BlurScale) 
          : (settings.effect2BlurScale !== undefined && settings.effect2BlurScale !== '' ? parseFloat(settings.effect2BlurScale) : 0.9),
        blurDuration: useOverride && overrideSettings.effect2BlurDuration !== undefined 
          ? parseFloat(overrideSettings.effect2BlurDuration) 
          : (settings.effect2BlurDuration !== undefined && settings.effect2BlurDuration !== '' ? parseFloat(settings.effect2BlurDuration) : 0.2)
      };
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          gsap.set(target, { transformOrigin: '0% 50%' });
          target.currentTween = gsap.to(target, {
            startAt: { filter: 'blur(0px)' },
            filter: `blur(${effect2Settings.blurAmount}px)`,
            scale: effect2Settings.blurScale,
            duration: effect2Settings.blurDuration,
            ease: 'sine',
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          target.currentTween = gsap.to(target, {
            filter: 'blur(0px)',
            scale: 1,
            ...animationProps,
            onComplete: () => {
              resetElement(target);
              target.currentTween = null;
            }
          });
        }
      };
      
      case 3: // Slide Text
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          const innerContent = target.innerHTML;
          target.innerHTML = `<div class="oh__inner">${innerContent}</div>`;
          target.classList.add('oh');
          gsap.set(target, { transformOrigin: '50% 50%' });
          target.currentTween = gsap.to(target.querySelector('.oh__inner'), {
            yPercent: -102,
            ...animationProps,
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          target.currentTween = gsap.to(target.querySelector('.oh__inner'), {
            startAt: { yPercent: 102 },
            yPercent: 0,
            ...animationProps,
            onComplete: () => {
              target.innerHTML = originalHTMLContent;
              target.classList.remove('oh');
              resetElement(target);
              target.currentTween = null;
            }
          });
        }
      };
      
      case 4: // Text Split
      const effect4Settings = {
        textXRange: useOverride && overrideSettings.effect4TextXRange !== undefined 
          ? parseInt(overrideSettings.effect4TextXRange) 
          : (settings.effect4TextXRange !== undefined && settings.effect4TextXRange !== '' ? parseInt(settings.effect4TextXRange) : 50),
        textYRange: useOverride && overrideSettings.effect4TextYRange !== undefined 
          ? parseInt(overrideSettings.effect4TextYRange) 
          : (settings.effect4TextYRange !== undefined && settings.effect4TextYRange !== '' ? parseInt(settings.effect4TextYRange) : 40),
        staggerAmount: useOverride && overrideSettings.effect4StaggerAmount !== undefined 
          ? parseFloat(overrideSettings.effect4StaggerAmount) 
          : (settings.effect4StaggerAmount !== undefined && settings.effect4StaggerAmount !== '' ? parseFloat(settings.effect4StaggerAmount) : 0.03)
      };
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: async (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          // Wait for SplitType to be available before importing TextSplitter
          await waitForSplitType();
          // Dynamically import TextSplitter only when needed
          const { TextSplitter } = await import('./textSplitter.js');
          target.textSplitter = new TextSplitter(target, { splitTypeTypes: 'chars' });
          target.currentTween = gsap.to(target.textSplitter.getChars(), {
            x: () => gsap.utils.random(-effect4Settings.textXRange, effect4Settings.textXRange),
            y: () => gsap.utils.random(-effect4Settings.textYRange, 0),
            autoAlpha: 0,
            stagger: {
              amount: effect4Settings.staggerAmount,
              from: 'random'
            },
            ...animationProps,
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          const chars = target.textSplitter?.getChars?.();
          if (!chars) {
            resetElement(target);
            target.currentTween = null;
            return;
          }
          target.currentTween = gsap.to(chars, {
            x: 0,
            y: 0,
            autoAlpha: 1,
            stagger: {
              amount: effect4Settings.staggerAmount,
              from: 'random'
            },
            ...animationProps,
            onComplete: () => {
              target.innerHTML = originalHTMLContent;
              resetElement(target);
              target.currentTween = null;
            }
          });
        }
      };
      
      case 5: // Character Shuffle
      const effect5Settings = {
        shuffleIterations: useOverride && overrideSettings.effect5ShuffleIterations !== undefined 
          ? parseInt(overrideSettings.effect5ShuffleIterations) 
          : (settings.effect5ShuffleIterations !== undefined && settings.effect5ShuffleIterations !== '' ? parseInt(settings.effect5ShuffleIterations) : 2),
        shuffleDuration: useOverride && overrideSettings.effect5ShuffleDuration !== undefined 
          ? parseFloat(overrideSettings.effect5ShuffleDuration) 
          : (settings.effect5ShuffleDuration !== undefined && settings.effect5ShuffleDuration !== '' ? parseFloat(settings.effect5ShuffleDuration) : 0.03),
        charDelay: useOverride && overrideSettings.effect5CharDelay !== undefined 
          ? parseFloat(overrideSettings.effect5CharDelay) 
          : (settings.effect5CharDelay !== undefined && settings.effect5CharDelay !== '' ? parseFloat(settings.effect5CharDelay) : 0.03)
      };
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: async (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          // Wait for SplitType to be available before importing TextSplitter
          await waitForSplitType();
          // Dynamically import TextSplitter only when needed
          const { TextSplitter } = await import('./textSplitter.js');
          target.textSplitter = new TextSplitter(target, { splitTypeTypes: 'chars' });
          target.currentTween = gsap.to(target.textSplitter.getChars(), {
            duration: 0.02,
            ease: 'none',
            autoAlpha: 0,
            stagger: {
              amount: 0.25,
              from: 'end'
            },
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          const chars = target.textSplitter?.getChars?.();
          if (!chars) {
            resetElement(target);
            target.currentTween = null;
            return;
          }
          const getRandomChar = () => {
            const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            return letters.charAt(Math.floor(Math.random() * letters.length));
          };
      
          const tl = gsap.timeline({
            onComplete: () => {
              resetElement(target);
              target.currentTween = null;
            }
          });
      
          chars.forEach((char, index) => {
            const originalChar = char.innerHTML;
      
            for (let i = 0; i < effect5Settings.shuffleIterations; i++) {
              tl.to(char, {
                duration: effect5Settings.shuffleDuration,
                textContent: getRandomChar(),
                autoAlpha: 1,
                ease: 'none'
              });
            }
      
            tl.to(char, {
              duration: 0.02,
              textContent: originalChar,
              autoAlpha: 1,
              ease: 'none'
            });
      
            tl.add('', index * effect5Settings.charDelay);
          });
      
          target.currentTween = tl;
        }
      };
      
      case 6: // Rotation
      const effect6Settings = {
        rotation: useOverride && overrideSettings.effect6Rotation !== undefined 
          ? parseInt(overrideSettings.effect6Rotation) 
          : (settings.effect6Rotation !== undefined && settings.effect6Rotation !== '' ? parseInt(settings.effect6Rotation) : -90),
        xPercent: useOverride && overrideSettings.effect6XPercent !== undefined 
          ? parseInt(overrideSettings.effect6XPercent) 
          : (settings.effect6XPercent !== undefined && settings.effect6XPercent !== '' ? parseInt(settings.effect6XPercent) : -5),
        originX6: useOverride && overrideSettings.effect6OriginX !== undefined 
          ? parseInt(overrideSettings.effect6OriginX) 
          : (settings.effect6OriginX !== undefined && settings.effect6OriginX !== '' ? parseInt(settings.effect6OriginX) : 0),
        originY6: useOverride && overrideSettings.effect6OriginY !== undefined 
          ? parseInt(overrideSettings.effect6OriginY) 
          : (settings.effect6OriginY !== undefined && settings.effect6OriginY !== '' ? parseInt(settings.effect6OriginY) : 100)
      };
      debug.log('Effect 6 settings loaded:', {
        rotation: effect6Settings.rotation,
        xPercent: effect6Settings.xPercent,
        originX6: effect6Settings.originX6,
        originY6: effect6Settings.originY6,
        fromWordPress: {
          rotation: settings.effect6Rotation,
          xPercent: settings.effect6XPercent,
          originX: settings.effect6OriginX,
          originY: settings.effect6OriginY
        }
      });
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          gsap.set(target, { transformOrigin: `${effect6Settings.originX6}% ${effect6Settings.originY6}%` });
          target.currentTween = gsap.to(target, {
            xPercent: effect6Settings.xPercent,
            rotation: effect6Settings.rotation,
            y: () => target.offsetWidth - target.offsetHeight,
            ...animationProps,
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          target.currentTween = gsap.to(target, {
            rotation: 0,
            xPercent: 0,
            y: 0,
            ...animationProps,
            onComplete: () => {
              resetElement(target);
              target.currentTween = null;
            },
          });
        }
      };
      
      case 7: // Move Away
      const effect7Settings = {
        moveDistance: useOverride && overrideSettings.effect7MoveDistance !== undefined && overrideSettings.effect7MoveDistance !== ''
          ? overrideSettings.effect7MoveDistance 
          : (settings.effect7MoveDistance !== undefined && settings.effect7MoveDistance !== '' ? settings.effect7MoveDistance : null)
      };
      return {
        offsetStartAmount: effectOffsetStart,
        offsetEndAmount: effectOffsetEnd,
        onEnter: (target) => {
          if (target.currentTween) target.currentTween.kill();
          resetElement(target);
          
          // Calculate move distance
          let animationProps_effect7 = { ...animationProps };
          if (effect7Settings.moveDistance) {
            // Parse the value (e.g., "100px" or "50%")
            const match = effect7Settings.moveDistance.match(/^([+-]?\d+(?:\.\d+)?)(px|%)$/i);
            if (match) {
              const number = parseFloat(match[1]);
              const unit = match[2].toLowerCase();
              if (unit === 'px') {
                // For pixels, use x property (negative to move left)
                animationProps_effect7.x = -Math.abs(number);
              } else if (unit === '%') {
                // For percentage, use xPercent (relative to element width, negative to move left)
                animationProps_effect7.xPercent = -Math.abs(number);
              }
            }
            // If parsing fails, fall through to default behavior
          }
          
          // If no custom distance set, use default behavior
          if (!effect7Settings.moveDistance || !animationProps_effect7.x && !animationProps_effect7.xPercent) {
            animationProps_effect7.x = () => -1 * (target.offsetWidth + target.offsetLeft);
          }
          
          target.currentTween = gsap.to(target, {
            ...animationProps_effect7,
            onComplete: () => {
              target.currentTween = null;
            }
          });
        },
        onLeave: (target) => {
          if (target.currentTween) target.currentTween.kill();
          target.currentTween = gsap.to(target, {
            x: 0,
            xPercent: 0,
            ...animationProps,
            onComplete: () => {
              resetElement(target);
              target.currentTween = null;
            },
          });
        }
      };
      
      default:
        return null;
    }
  }

  // Get the default effect configuration
  debug.group('Effect Configuration');
  debug.log('Building default effect for selected effect:', selectedEffect);
  const defaultEffect = buildEffect(selectedEffect);

  if (!defaultEffect) {
    console.warn(`Context-Aware Animation: Invalid effect selected: ${selectedEffect}`);
    debug.error('Invalid effect selected:', selectedEffect);
    debug.groupEnd();
    return;
  }

  debug.log('Default effect built successfully:', {
    offsetStartAmount: defaultEffect.offsetStartAmount,
    offsetEndAmount: defaultEffect.offsetEndAmount,
    hasOnEnter: typeof defaultEffect.onEnter === 'function',
    hasOnLeave: typeof defaultEffect.onLeave === 'function'
  });
  
  // Log effect mappings
  debug.log('Effect mappings:', effectMappings);
  debug.groupEnd();

  // Store ScrollTrigger instances for cleanup
  let scrollTriggerInstances = [];
  
  // Track which effect is currently active (for smooth transitions between different effects)
  let currentActiveEffect = null;
  let activeTriggersMap = new Map(); // Map to track which triggers are active and their effects

  // Simple 3-tier effect lookup: direct mapping > ancestor mapping > global default
  // Returns an object with effect number and optional override settings
  function getEffectForElement(element) {
    // Priority 1: Direct mapping on this element
    for (const mapping of effectMappings) {
      const { selector, effect, overrideEnabled, settings } = mapping;
      if (!selector || !effect) continue;
      try {
        if (element.matches?.(selector.trim())) {
          debug.log('Direct mapping match:', selector, '-> Effect', effect, 'Override:', overrideEnabled);
          return {
            effectNumber: parseInt(effect),
            overrideEnabled: overrideEnabled === true,
            settings: overrideEnabled ? settings : null
          };
        }
      } catch (e) {
        debug.warn('Invalid selector in mapping:', selector, e);
      }
    }
    
    // Priority 2: Inherited from mapped ancestor (closest wins)
    for (const mapping of effectMappings) {
      const { selector, effect, overrideEnabled, settings } = mapping;
      if (!selector || !effect) continue;
      try {
        if (element.closest?.(selector.trim())) {
          debug.log('Ancestor mapping match:', selector, '-> Effect', effect, 'Override:', overrideEnabled);
          return {
            effectNumber: parseInt(effect),
            overrideEnabled: overrideEnabled === true,
            settings: overrideEnabled ? settings : null
          };
        }
      } catch (e) {
        debug.warn('Invalid selector in mapping:', selector, e);
      }
    }
    
    // Priority 3: Global default
    return null;
  }

  // Function to create ScrollTriggers for each content block
  function createScrollTriggers() {
    debug.group('ScrollTrigger Creation');
    debug.log('Creating ScrollTriggers for', contentBlocks.length, 'content block(s)');
    
    // Kill existing triggers
    if (scrollTriggerInstances.length > 0) {
      debug.log('Killing', scrollTriggerInstances.length, 'existing trigger(s)');
      scrollTriggerInstances.forEach(instance => instance.kill());
    }
    scrollTriggerInstances = [];
    
    // Reset tracking when recreating triggers
    currentActiveEffect = null;
    activeTriggersMap.clear();
    
    if (contentBlocks.length === 0) {
      console.warn('Context-Aware Animation: No content blocks found.');
      debug.warn('No content blocks found - cannot create ScrollTriggers');
      debug.groupEnd();
      return;
    }
    
    // Calculate logo offset once globally for all triggers
    const elementOffsetTop = getElementTopOffset(logoElement);
    
    // Pre-build effects for each content block and cache them
    const blockEffects = contentBlocks.map((block, index) => {
      const mappedResult = getEffectForElement(block);
      
      // Determine effect number and override settings
      let effectNumber, overrideSettings;
      if (mappedResult !== null) {
        effectNumber = mappedResult.effectNumber;
        overrideSettings = mappedResult.overrideEnabled ? mappedResult.settings : null;
      } else {
        effectNumber = selectedEffect;
        overrideSettings = null;
      }
      
      const effect = buildEffect(effectNumber, overrideSettings);
      
      debug.log(`Block ${index + 1} effect:`, {
        block,
        mappedResult,
        usedEffect: effectNumber,
        hasOverride: overrideSettings !== null,
        isDefault: mappedResult === null
      });
      
      return {
        block,
        effect,
        effectNumber,
        overrideSettings
      };
    });
    
    blockEffects.forEach(({ block, effect, effectNumber }, index) => {
      if (!effect) {
        debug.warn(`Skipping block ${index + 1} - invalid effect`);
        return;
      }
      
      const startOffset = elementOffsetTop + effect.offsetStartAmount + globalOffset;
      const endOffset = elementOffsetTop - effect.offsetEndAmount + globalOffset;

      debug.log(`Creating ScrollTrigger ${index + 1}/${contentBlocks.length}`, {
        block,
        effectNumber,
        logoOffsetTop: elementOffsetTop,
        globalOffset,
        start: `top ${startOffset}px`,
        end: `bottom ${endOffset}px`
      });

      const triggerId = `trigger_${index}`;

      const trigger = ScrollTrigger.create({
        trigger: block,
        start: () => `top ${elementOffsetTop + effect.offsetStartAmount + globalOffset}px`,
        end: () => `bottom ${elementOffsetTop - effect.offsetEndAmount + globalOffset}px`,

        onEnter: () => {
          debug.log('ScrollTrigger: onEnter', block, 'Effect:', effectNumber);
          
          // Track this trigger as active
          activeTriggersMap.set(triggerId, { effect, effectNumber });
          
          // If no effect is currently active, or if we're switching to a different effect
          if (currentActiveEffect === null) {
            debug.log('First trigger activated, calling effect.onEnter');
            currentActiveEffect = effectNumber;
            effect.onEnter(logoElement);
          } else if (currentActiveEffect !== effectNumber) {
            // Different effect - transition: leave current, enter new
            debug.log('Switching effect from', currentActiveEffect, 'to', effectNumber);
            const previousEffect = buildEffect(currentActiveEffect, null);
            if (previousEffect) {
              previousEffect.onLeave(logoElement);
            }
            currentActiveEffect = effectNumber;
            setTimeout(() => effect.onEnter(logoElement), 50); // Small delay for smooth transition
          } else {
            debug.log('Same effect already active, skipping onEnter');
          }
        },
        onLeaveBack: () => {
          debug.log('ScrollTrigger: onLeaveBack', block, 'Effect:', effectNumber);
          
          // Remove this trigger from active map
          activeTriggersMap.delete(triggerId);
          
          // Check if any triggers are still active
          if (activeTriggersMap.size === 0) {
            debug.log('Last trigger deactivated, calling effect.onLeave');
            effect.onLeave(logoElement);
            currentActiveEffect = null;
          } else {
            // Find the highest priority remaining active trigger
            const remainingTriggers = Array.from(activeTriggersMap.values());
            const nextEffect = remainingTriggers[remainingTriggers.length - 1];
            if (nextEffect && nextEffect.effectNumber !== currentActiveEffect) {
              debug.log('Transitioning back to effect:', nextEffect.effectNumber);
              effect.onLeave(logoElement);
              currentActiveEffect = nextEffect.effectNumber;
              setTimeout(() => nextEffect.effect.onEnter(logoElement), 50);
            }
          }
        },
        onLeave: () => {
          debug.log('ScrollTrigger: onLeave', block, 'Effect:', effectNumber);
          
          // Remove this trigger from active map
          activeTriggersMap.delete(triggerId);
          
          // Check if any triggers are still active
          if (activeTriggersMap.size === 0) {
            debug.log('Last trigger deactivated, calling effect.onLeave');
            effect.onLeave(logoElement);
            currentActiveEffect = null;
          } else {
            // Find the next active trigger
            const remainingTriggers = Array.from(activeTriggersMap.values());
            const nextEffect = remainingTriggers[remainingTriggers.length - 1];
            if (nextEffect && nextEffect.effectNumber !== currentActiveEffect) {
              debug.log('Transitioning to effect:', nextEffect.effectNumber);
              effect.onLeave(logoElement);
              currentActiveEffect = nextEffect.effectNumber;
              setTimeout(() => nextEffect.effect.onEnter(logoElement), 50);
            }
          }
        },
        onEnterBack: () => {
          debug.log('ScrollTrigger: onEnterBack', block, 'Effect:', effectNumber);
          
          // Track this trigger as active
          activeTriggersMap.set(triggerId, { effect, effectNumber });
          
          // If no effect is currently active, or if we're switching to a different effect
          if (currentActiveEffect === null) {
            debug.log('First trigger activated, calling effect.onEnter');
            currentActiveEffect = effectNumber;
            effect.onEnter(logoElement);
          } else if (currentActiveEffect !== effectNumber) {
            // Different effect - transition
            debug.log('Switching effect from', currentActiveEffect, 'to', effectNumber);
            const previousEffect = buildEffect(currentActiveEffect, null);
            if (previousEffect) {
              previousEffect.onLeave(logoElement);
            }
            currentActiveEffect = effectNumber;
            setTimeout(() => effect.onEnter(logoElement), 50);
          } else {
            debug.log('Same effect already active, skipping onEnter');
          }
        }
      });
      
      scrollTriggerInstances.push(trigger);
      debug.log(`ScrollTrigger ${index + 1} created successfully`);
    });
    
    debug.log('All ScrollTriggers created:', scrollTriggerInstances.length);
    debug.groupEnd();
  }

  // Wait until the page is fully loaded to create the ScrollTriggers
  window.addEventListener('load', () => {
    debug.log('Page loaded, creating ScrollTriggers');
    createScrollTriggers();
  });

  // Update position dynamically on resize
  window.addEventListener('resize', () => {
    debug.log('Window resized, refreshing ScrollTrigger');
    ScrollTrigger.refresh();
  });
  
  debug.log('Event listeners registered');

})(); // End of IIFE

