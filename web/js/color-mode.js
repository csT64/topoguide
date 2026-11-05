/*!
 * Bascule thème clair / sombre (Bootstrap 5.3, attribut data-bs-theme sur <html>).
 * Charte graphique Tourisme 64 — chargé dans <head> (AppAsset, POS_HEAD) pour appliquer
 * le thème avant l'affichage. Choix mémorisé dans localStorage, sinon préférence du système.
 * Le bouton #theme-toggle contient deux pictos (lune / soleil) affichés par composants.css
 * et un texte masqué visuellement (#theme-toggle-texte), son nom accessible.
 */
(() => {
    'use strict'

    const getStoredTheme = () => { try { return localStorage.getItem('theme') } catch (e) { return null } }
    const setStoredTheme = theme => { try { localStorage.setItem('theme', theme) } catch (e) { /* stockage indisponible */ } }

    const getPreferredTheme = () => {
        const stored = getStoredTheme()
        if (stored === 'light' || stored === 'dark') return stored
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    const setTheme = theme => {
        document.documentElement.setAttribute('data-bs-theme', theme)
    }

    const updateToggle = theme => {
        const toggle = document.getElementById('theme-toggle')
        if (!toggle) return
        const label = theme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre'
        const texte = document.getElementById('theme-toggle-texte')
        if (texte) texte.textContent = label
        toggle.setAttribute('title', label)
    }

    setTheme(getPreferredTheme())

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (!getStoredTheme()) {
            const theme = getPreferredTheme()
            setTheme(theme)
            updateToggle(theme)
        }
    })

    document.addEventListener('DOMContentLoaded', () => {
        updateToggle(getPreferredTheme())

        const toggle = document.getElementById('theme-toggle')
        if (toggle) {
            toggle.addEventListener('click', () => {
                const newTheme = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark'
                setStoredTheme(newTheme)
                setTheme(newTheme)
                updateToggle(newTheme)
            })
        }
    })
})()
