/**
 * Vite plugin to extract critical CSS for above-the-fold content.
 *
 * This plugin analyzes the built CSS and extracts rules for elements
 * that appear in the initial viewport (navbar, banner, layout basics).
 * The critical CSS is saved separately for inlining in the HTML head.
 */

import { parse, stringify } from 'css'
import { readdirSync, readFileSync, writeFileSync } from 'fs'
import { join } from 'path'

// Selectors that are critical for above-the-fold rendering
const CRITICAL_SELECTORS = [
  // Reset & base styles
  /^html$/,
  /^body$/,
  /^\*$/,
  /^:root$/,

  // Layout
  /^\.container$/,
  /^\.row$/,
  /^\[hidden\]$/,

  // Typography basics
  /^a$/,
  /^a:/,
  /^h1/,
  /^p$/,
  /^img$/,
  /^ul$/,

  // Navbar (always visible)
  /^\.navbar/,
  /^\.nav-/,
  /^\.nav\b/,

  // Logo
  /^\.logo-font$/,

  // Banner (homepage hero)
  /^\.banner/,
  /^\.home-page\s+\.banner/,

  // Article previews (homepage feed)
  /^\.article-preview/,
  /^\.preview-link/,
  /^\.article-meta/,
  /^\.feed-toggle/,
  /^\.tag-list/,
  /^\.tag-default/,
  /^\.tag-pill/,
  /^\.tag-outline/,

  // Article preview h1 (descendant selector)
  /\.preview-link\s+h1/,

  // Buttons (appear in banner)
  /^\.btn$/,
  /^\.btn:/,
  /^\.btn-primary/,
  /^\.btn-outline/,

  // User pic in navbar
  /^\.user-pic$/,

  // Pull utilities
  /^\.pull-xs-right/,

  // Font-face declarations (needed for text rendering)
  /^@font-face$/,
]

function isCriticalSelector(selector) {
  return CRITICAL_SELECTORS.some(pattern => pattern.test(selector))
}

function isCriticalRule(rule) {
  // Include all @font-face declarations (including fallbacks)
  if (rule.type === 'font-face') {
    return true
  }

  if (rule.type === 'rule' && rule.selectors) {
    return rule.selectors.some(sel => isCriticalSelector(sel))
  }

  if (rule.type === 'media' && rule.rules) {
    // Include media query if it contains critical rules
    const criticalRules = rule.rules.filter(r => isCriticalRule(r))
    if (criticalRules.length > 0) {
      rule.rules = criticalRules
      return true
    }
  }

  return false
}

export default function criticalCssPlugin() {
  return {
    name: 'critical-css',
    apply: 'build',

    closeBundle() {
      const distDir = 'app/public/dist/assets'
      const files = readdirSync(distDir)
      const cssFile = files.find(f => f.endsWith('.css'))

      if (!cssFile) {
        console.warn('[critical-css] No CSS file found in dist/assets')
        return
      }

      const cssPath = join(distDir, cssFile)
      const cssContent = readFileSync(cssPath, 'utf-8')

      try {
        const ast = parse(cssContent)
        const criticalRules = ast.stylesheet.rules.filter(rule => isCriticalRule(rule))

        const criticalAst = {
          type: 'stylesheet',
          stylesheet: { rules: criticalRules }
        }

        const criticalCss = stringify(criticalAst, { compress: true })

        // Save critical CSS
        const criticalPath = join(distDir, 'critical.css')
        writeFileSync(criticalPath, criticalCss)

        console.log(`[critical-css] Extracted ${(criticalCss.length / 1024).toFixed(1)}KB critical CSS`)
        console.log(`[critical-css] Full CSS: ${(cssContent.length / 1024).toFixed(1)}KB`)
      } catch (err) {
        console.error('[critical-css] Failed to parse CSS:', err.message)
      }
    }
  }
}
