# Polices

## Atkinson Hyperlegible (versionnée — OFL)

Police accessible prioritaire pour la génération PDF et la page HTML.
Licence : SIL Open Font License (open source).

- `AtkinsonHyperlegible-Regular.ttf`
- `AtkinsonHyperlegible-Bold.ttf`
- `AtkinsonHyperlegible-Italic.ttf`
- `AtkinsonHyperlegible-BoldItalic.ttf`

Chargée via `@font-face file://` dans `views/topoguide/fiche.php` (mode PDF uniquement).
En mode HTML, elle est déclarée en premier dans la stack `font-family` — le navigateur
la charge depuis le cache OS si installée, sinon fallback Arial.

## Polices propriétaires (non versionnées)

- `futuramediumbt.ttf`  — ancienne police corps (FuturaMediumBT)
- `FuturaHeavyfont.ttf` — ancienne police titres (Futura Heavy)
