# The Resilience Project — Design System
## UI/UX Design & Engineering Guide

> **Mission**: To inspire happiness and change lives through a platform that feels human, hopeful, and honest.

---

## Table of Contents
1. [App Categories](#app-categories)
2. [Shared Tech Stack](#shared-tech-stack)
3. [Brand Principles](#brand-principles)
4. [Typography System](#typography-system)
5. [Color Palette](#color-palette)
6. [Iconography](#iconography)
7. [Photography Guidelines](#photography-guidelines)
8. [Layout & Spacing](#layout--spacing)
9. [Components](#components)
10. [Audience-Specific Guidelines](#audience-specific-guidelines)
11. [Implementation Checklist](#implementation-checklist)

---

## App Categories

All apps live under `/apps/` in the repository. They fall into two categories:

### Public-Facing Apps
Apps seen by external users (schools, workplaces, partners). **Full brand treatment required** — follow all typography, colour, iconography, and photography guidelines in this document.

### Internal Tools
Developer and staff tools (e.g., `apps/dashboard`, `apps/conf-uploads`). These prioritise function over brand:
- Use **shadcn/ui defaults** — no custom brand theming required
- Use the shared tech stack below
- Keep the UI clean and functional — no need for hand-drawn icons, brand photography, or diagonal wave elements
- Brand colours may be used for accent but are not required

---

## Shared Tech Stack

All new web apps should use this stack unless there is a specific reason not to:

- **Framework**: Next.js 15+ (App Router, `src/` directory)
- **UI Library**: React 19+
- **Styling**: Tailwind CSS v4 + shadcn/ui
- **Charts**: Recharts (when data visualisation is needed)
- **Data Fetching**: @tanstack/react-query
- **Language**: TypeScript (strict mode)

### Scaffolding a New App

```bash
cd apps/
npx create-next-app@latest <app-name> --typescript --tailwind --eslint --app --src-dir --use-npm
cd <app-name>
npx shadcn@latest init -d
```

### Conventions
- Each app is self-contained with its own `package.json`, `node_modules`, and build config
- Shared types or utilities across apps should be extracted to a shared package if needed
- Australian English spelling in all code, comments, and UI copy

---

## Brand Principles

### Core Values
Our brand is built on **connection**. Every element should help people feel something real:

- **Human**: Approachable, honest, imperfect in a good way
- **Hopeful**: Optimistic, warm, full of life
- **Grounded**: Real people, real emotions, everyday joy

### Voice & Tone
For the **Workplaces/Adults** audience:
- Clear and confident, yet professional
- Move from playfulness to clarity, reflection, and authenticity
- Lean into symbolism using strong, simple forms
- Bridge empathy and practicality

**Key phrase**: "Fewer faces, more meaning"

---

## Typography System

### Font Stack

We use **two primary fonts** to strike the right balance:

#### 1. Public Sans (Primary)
- **Purpose**: Main font - simple, versatile, easy to read
- **Use for**: Body text, UI elements, buttons, labels, navigation
- **Weights**: Regular (400), Semi Bold (600), Bold (700), Extra Bold (800)
- **Why**: Keeps us looking professional and trustworthy

#### 2. PT Serif (Headings)
- **Purpose**: Adds warmth and editorial quality
- **Use for**: Page headings, section titles
- **Weight**: Regular (400)
- **Why**: Creates hierarchy and rhythm

#### 3. Caveat (Accent - Use Sparingly)
- **Purpose**: Playful accent for callouts
- **Use for**: Highlighted words, emphasis, handwritten feel
- **When**: Only for small moments of warmth without losing clarity
- **⚠️ Warning**: Do NOT overuse in workplace contexts

### Type Scale

```css
/* Display Titles */
font-family: 'Public Sans', sans-serif;
font-weight: 800; /* Extra Bold */
font-size: 48px - 72px;
color: #000000;

/* Page Headings (H1) */
font-family: 'PT Serif', serif;
font-weight: 400; /* Regular */
font-size: 36px - 48px;
color: #000000;

/* Section Headings (H2) */
font-family: 'PT Serif', serif;
font-weight: 400;
font-size: 28px - 36px;
color: #000000;

/* Subheadings (H3) */
font-family: 'Public Sans', sans-serif;
font-weight: 600; /* Semi Bold */
font-size: 20px - 24px;
color: #000000;

/* Labels & Small Headers */
font-family: 'Public Sans', sans-serif;
font-weight: 700; /* Bold */
font-size: 12px - 14px;
text-transform: uppercase;
letter-spacing: 0.05em;
color: #000000;

/* Body Text */
font-family: 'Public Sans', sans-serif;
font-weight: 400; /* Regular */
font-size: 16px - 18px;
line-height: 1.6;
color: #000000;

/* Body Small */
font-family: 'Public Sans', sans-serif;
font-weight: 400;
font-size: 14px - 16px;
line-height: 1.5;
color: #000000;

/* Buttons */
font-family: 'Public Sans', sans-serif;
font-weight: 700; /* Bold */
font-size: 14px - 16px;
text-transform: uppercase;
letter-spacing: 0.05em;

/* Callout/Emphasis Words */
font-family: 'Caveat', cursive;
font-weight: 400;
font-size: 18px - 24px;
/* Use for single words or short phrases only */
```

### Typography Rules

**✅ DO:**
- Write all copy in black (#000000)
- Use different weights and styles to create hierarchy
- Keep body copy readable (16px minimum)
- Use PT Serif for editorial, storytelling feel
- Reserve Caveat for emotional highlights

**❌ DON'T:**
- Mix too many font weights in one section
- Use Caveat for entire sentences in workplace context
- Use colored text for body copy (black only)
- Create hierarchy through color alone

---

## Color Palette

### Primary Brand Colors (MOST USED)

These are your foundation. Use them confidently:

```css
/* Blue 01 - Primary Brand Blue */
--blue-01: #00B0CA;
--blue-01-rgb: rgb(0, 176, 202);
--blue-01-pms: 3125 C;
--blue-01-cmyk: C:50 M:0 Y:20 B:0

/* Stone 01 - Primary Neutral */
--stone-01: #F2EEEA;
--stone-01-pms: 7534 C (at 50%)
--stone-01-cmyk: C:5 M:5 Y:15 B:8 (at 50%)

/* Black */
--black: #000000;
--black-pms: Black C;
--black-cmyk: C:20 M:20 Y:20 B:100

/* White */
--white: #FFFFFF;
--white-pms: Paper;
--white-cmyk: C:0 M:0 Y:0 B:0
```

### Secondary Brand Colors

```css
/* Blue 02 - Light Blue */
--blue-02: #8ED9E7;
--blue-02-pms: 3105 C;
--blue-02-cmyk: C:44 M:0 Y:11 B:0

/* Stone 02 - Mid Neutral */
--stone-02: #E4DCD5;
--stone-02-pms: 7534 C;
--stone-02-cmyk: C:5 M:5 Y:15 B:8

/* Blue 03 - Lightest Blue */
--blue-03: #D9EAF1;
--blue-03-pms: 290 C;
--blue-03-cmyk: C:23 M:0 Y:1 B:0

/* Buttons Blue */
--button-blue: #4C7ECF;
--button-blue-pms: 2718 C;
--button-blue-cmyk: C:65 M:45 Y:0 B:0
```

### G.E.M. Model Colors

**⚠️ IMPORTANT**: These colors represent specific wellbeing concepts. **Do NOT use outside of G.E.M. context.**

```css
/* Gratitude (Orange/Yellow) */
--gratitude: #FFA02F;
--gratitude-light: rgba(255, 160, 47, 0.5);
--gratitude-pop: #FFE282;

/* Empathy (Teal) */
--empathy: #00B092;
--empathy-light: rgba(0, 176, 146, 0.5);
--empathy-pop: #80E9BC;

/* Mindfulness (Purple) */
--mindfulness: #9278D1;
--mindfulness-light: rgba(146, 120, 209, 0.5);
--mindfulness-pop: #D7BEFC;

/* Emotional Literacy (Pink/Magenta) */
--emotional-literacy: #DC0451;
--emotional-literacy-light: rgba(220, 4, 81, 0.5);
--emotional-literacy-pop: #FFA6D7;

/* Generic (Not associated with any pillar) */
--generic: #FF887E;
```

### Color Usage Rules

**✅ DO:**
- Use Blue 01 (#00B0CA) as primary brand color for key actions, headers, highlights
- Use Stone backgrounds to create warmth and depth
- Use brand blues, neutrals, and single highlight tones
- Use G.E.M. colors ONLY when referencing the wellbeing framework
- Maintain consistent button colors (#4C7ECF)
- Use white space generously

**❌ DON'T:**
- Use gradients or off-brand color tones
- Mix G.E.M. colors with general UI elements
- Use full color blocks without space to breathe
- Create visual noise through too many colors

---

## Iconography

### Icon Style: Hand-Drawn

Icons should **always feel hand-drawn** to convey honesty and humanity.

#### Workplace/Adults Icon Guidelines

For the Workplaces Hub, icons follow these rules:

**Style Characteristics:**
- **Objects only** - no faces, fewer limbs
- Simple, strong forms
- Black outline, minimal detail
- Slightly imperfect lines (wobble is good!)
- Lean into symbolism
- Professional yet approachable

**Examples from Guidelines:**
- Brain (learning, mindfulness)
- Globe (whole-community)
- Trophy/Award (achievement)
- Lightbulb (ideas, insight)
- Leaf (growth)
- Book/Journal (reflection)
- Glasses (perspective)
- Plant pot (nurture)

**Accent Colors:**
- Can use brand Blue 01 (#00B0CA) or teal as accent
- Can use G.E.M. colors when representing specific pillars
- Default to black (#000000) for neutrality

### Implementation

```tsx
// Icon sizes
--icon-sm: 16px;
--icon-md: 24px;
--icon-lg: 32px;
--icon-xl: 48px;

// Stroke width
stroke-width: 2px - 3px; /* Slightly varied for hand-drawn feel */

// Colors
stroke: #000000; /* Default */
stroke: #00B0CA; /* Accent - use sparingly */
fill: transparent; /* Usually no fill, outline only */
```

**✅ DO:**
- Keep icons simple and symbolic
- Use consistent stroke weight (2-3px)
- Embrace slight imperfections
- Use objects that represent concepts
- Maintain hand-drawn aesthetic

**❌ DON'T:**
- Use faces or limbs in workplace context
- Make icons too detailed or complex
- Use perfectly straight lines (add subtle wobble)
- Mix icon styles from different audiences

---

## Photography Guidelines

### Photography Philosophy

**We aren't afraid to show photos.** In fact, we love it.

Photography helps capture what TRP is really about:
- Real people
- Real emotions
- Real connection

### What We Love

Every photo should reflect the heart of our mission. Show:

- ✅ **Big smiles, genuine joy, and connection**
- ✅ **Moments in motion** - laughter, listening, reflection
- ✅ **Soft focus or background blur** to create depth and presence
- ✅ **Bright color and contrast** that makes optimism visible
- ✅ **Natural negative space** for copy or sketches
- ✅ **Rounded corners** to make it approachable and human

### Photography Should Make People Feel

- **Belonging**
- **Kindness**
- **Calm**

### Angles & Composition

Use a mix of:

1. **Wide scenes** - show community and connection
2. **Medium shots** - capture small group dynamics and belonging
3. **Close-ups** - highlight emotion, focus, or warmth
4. **Over-the-shoulder or candid views** - draw people into lived moments
5. **Creative perspectives** - find beauty in the ordinary (hands drawing, people listening, laughter in background)

### Technical Specs

```css
/* Image treatment */
border-radius: 16px - 24px; /* Rounded corners */
aspect-ratio: Varied based on context;

/* Lighting */
- Natural light preferred
- Gentle warmth
- Candid honesty
```

### What to Avoid

**❌ DON'T:**
- Use stock imagery
- Show posed perfection
- Mix playful early-years sketches in workplace contexts
- Show inauthentic expressions
- Use harsh lighting or overly polished shots

### Image Selection Checklist

Before using an image, ask:

- [ ] Does it show **real people** (not models)?
- [ ] Does it capture **authentic emotion**?
- [ ] Does it reflect **joy, reflection, or togetherness**?
- [ ] Is it showing a **genuine moment** (not posed)?
- [ ] Does it have **natural light** and **gentle warmth**?
- [ ] Does it feel **hopeful and human**?

---

## Layout & Spacing

### Grid System

Use consistent grid patterns with the signature diagonal wave element.

```css
/* Container widths */
--container-sm: 640px;
--container-md: 768px;
--container-lg: 1024px;
--container-xl: 1280px;

/* Grid gaps */
--gap-xs: 8px;
--gap-sm: 16px;
--gap-md: 24px;
--gap-lg: 32px;
--gap-xl: 48px;
--gap-2xl: 64px;
```

### Spacing Scale

```css
/* Spacing tokens */
--space-xs: 4px;
--space-sm: 8px;
--space-md: 16px;
--space-lg: 24px;
--space-xl: 32px;
--space-2xl: 48px;
--space-3xl: 64px;
--space-4xl: 96px;
```

### Visual Rhythm Principles

**"Space to breathe"** - Key principle throughout

**✅ DO:**
- Use ample white space around content
- Create visual hierarchy through spacing
- Use the diagonal wave/curve motif as a design element
- Balance busy sections with quiet sections
- Think about "page before and page after" flow

**❌ DON'T:**
- Overcrowd sections with content
- Use equal spacing everywhere (create rhythm)
- Fill every pixel (embrace negative space)

### The Diagonal Wave

The signature diagonal curve appears throughout TRP materials:

```css
/* Diagonal section divider */
.diagonal-wave {
  background: linear-gradient(
    to bottom right,
    var(--blue-01) 0%,
    var(--blue-01) 50%,
    var(--stone-01) 50%,
    var(--stone-01) 100%
  );
}

/* Alternative: SVG clip-path for smoother curves */
clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
```

---

## Components

### Buttons

Buttons are a key interaction point. Keep them clear and consistent.

#### Primary Button

```tsx
<Button variant="primary">
  SEE HOW WE WORK
</Button>

// Styles
.button-primary {
  background: #4C7ECF;
  color: #FFFFFF;
  font-family: 'Public Sans', sans-serif;
  font-weight: 700;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 24px;
  border-radius: 24px; /* Fully rounded */
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.button-primary:hover {
  background: #3d6bb8;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 126, 207, 0.3);
}
```

#### Secondary Button

```tsx
<Button variant="secondary">
  FIND OUT MORE
</Button>

// Styles
.button-secondary {
  background: transparent;
  color: #4C7ECF;
  font-family: 'Public Sans', sans-serif;
  font-weight: 700;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 24px;
  border-radius: 24px;
  border: 2px solid #4C7ECF;
  cursor: pointer;
  transition: all 0.2s ease;
}

.button-secondary:hover {
  background: #4C7ECF;
  color: #FFFFFF;
}
```

### Cards

Cards should have rounded corners and feel approachable.

```tsx
// Card with GEM color accent
<Card accentColor="empathy">
  <CardIcon>🌱</CardIcon>
  <CardTitle>Empathy</CardTitle>
  <CardDescription>
    Putting ourselves in the shoes of others...
  </CardDescription>
</Card>

// Styles
.card {
  background: #FFFFFF;
  border-radius: 16px;
  padding: 32px;
  border-left: 4px solid var(--accent-color);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
}

.card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  transform: translateY(-4px);
}
```

### Callouts

Use callouts sparingly for important information.

```tsx
<Callout variant="empathy">
  <CalloutIcon>
    {/* Hand-drawn flower icon */}
  </CalloutIcon>
  <CalloutTitle>Did You Know?</CalloutTitle>
  <CalloutContent>
    Empathy helps us build stronger connections...
  </CalloutContent>
</Callout>

// Styles
.callout {
  background: var(--empathy-light);
  border-radius: 16px;
  padding: 24px;
  border: 2px solid var(--empathy);
}
```

### Navigation

Keep navigation clear and simple.

```tsx
// Header navigation
<Navigation>
  <NavLink>Programs</NavLink>
  <NavLink>Early Years</NavLink>
  <NavLink>Schools</NavLink>
  <NavLink>Workplace</NavLink>
  <NavLink>About</NavLink>
  <NavLink>Benefits</NavLink>
  <NavLink>Resources & Blog</NavLink>
  <NavLink>Contact</NavLink>
</Navigation>

// Styles
.nav-link {
  font-family: 'Public Sans', sans-serif;
  font-weight: 400;
  font-size: 14px;
  color: #000000;
  text-decoration: none;
  padding: 8px 16px;
  transition: color 0.2s ease;
}

.nav-link:hover {
  color: #00B0CA;
}

.nav-link.active {
  font-weight: 700;
  color: #00B0CA;
}
```

---

## Audience-Specific Guidelines

### Workplaces/Adults Context

Since this is the **Workplaces Hub**, follow these specific guidelines:

#### Tone & Approach
- **Clear, confident, and professional** - yet unmistakably TRP
- Move from overt playfulness to **clarity, reflection, and authenticity**
- Qualities that mirror TRP's mission to bring wellbeing into everyday life and work

#### Visual Approach
- **Fewer faces, more meaning**
- Lean into symbolism, using strong, simple forms like:
  - Lightbulb (insight)
  - Leaf (growth)
  - Key (self-awareness)
- Keep brand approachable yet professional
- Bridge empathy and practicality

#### Color Usage
- Limit palette to brand blues, neutrals, and single highlight tone
- Use G.E.M. colors ONLY where it reinforces the wellbeing connection
- Not as decoration

#### Content Guidelines
- Lead with strong mission language - short, confident, and optimistic
- Keep tone humble and open, not overly polished or corporate
- Use illustrations sparingly but meaningfully
- Mainly for visual rhythm and warmth, not decoration

#### Photography
- Lead with **authentic photography** - diverse, real, approachable
- Limit color palette to brand blues, neutrals, and single highlight
- Avoid overloading with text
- Keep key benefits and outcomes simple and scannable

#### Layout
- **Maintain consistent spacing and visual rhythm**
- Give the design room to breathe
- Use the G.E.M. model colors only where it reinforces the wellbeing connection (not as decoration)
- Ensure all pricing or program structures feel clear, calm, and transparent

---

## Implementation Checklist

### For Designers

Before finalizing any mockup, check:

#### Typography
- [ ] Using Public Sans for UI/body text?
- [ ] Using PT Serif for headings?
- [ ] All copy in black (#000000)?
- [ ] Caveat used sparingly (if at all)?
- [ ] Clear typographic hierarchy established?
- [ ] Minimum 16px for body text?

#### Color
- [ ] Using approved brand colors only?
- [ ] NO gradients or off-brand tones?
- [ ] G.E.M. colors used ONLY in wellbeing context?
- [ ] Primary blue (#00B0CA) used for key actions?
- [ ] Enough white space / breathing room?

#### Imagery
- [ ] Using real photos (not stock)?
- [ ] Photos show authentic emotion?
- [ ] Rounded corners applied (16-24px)?
- [ ] Natural light and warmth present?
- [ ] Candid, genuine moments captured?

#### Icons
- [ ] Hand-drawn style maintained?
- [ ] Objects only (no faces for workplace)?
- [ ] Black outline with minimal detail?
- [ ] Slight imperfection present (wobbly lines)?
- [ ] Consistent stroke weight (2-3px)?

#### Layout
- [ ] Ample white space around content?
- [ ] Diagonal wave element used appropriately?
- [ ] Visual rhythm created through spacing?
- [ ] Page flow considered (before/after)?
- [ ] Mobile responsive design?

#### Components
- [ ] Buttons using correct styles and colors?
- [ ] Cards have rounded corners?
- [ ] Navigation clear and simple?
- [ ] Callouts used sparingly?

#### Tone & Voice
- [ ] Clear and confident (not corporate)?
- [ ] Professional yet approachable?
- [ ] Authenticity over perfection?
- [ ] Short, warm, conversational sentences?

### For Engineers

Before implementing designs, ensure:

#### CSS/Styling
- [ ] CSS custom properties defined for colors?
- [ ] Typography scale implemented with correct weights?
- [ ] Spacing tokens used consistently?
- [ ] Border radius applied to interactive elements?
- [ ] Hover states defined for buttons/links?
- [ ] Transitions smooth and performant?

#### Components
- [ ] Reusable components created for buttons, cards, etc.?
- [ ] Props for variants (primary/secondary, GEM colors)?
- [ ] Accessibility attributes included (aria-labels, roles)?
- [ ] Keyboard navigation supported?
- [ ] Focus states visible and clear?

#### Images
- [ ] Next.js Image component used for optimization?
- [ ] Alt text provided for all images?
- [ ] Loading states handled gracefully?
- [ ] Responsive image sizing implemented?
- [ ] Image hostnames configured in next.config.js?

#### Responsive Design
- [ ] Mobile-first approach?
- [ ] Breakpoints align with design system?
- [ ] Touch targets at least 44x44px?
- [ ] Content readable on small screens?
- [ ] Navigation adapted for mobile?

#### Performance
- [ ] Fonts optimized and preloaded?
- [ ] Images lazy-loaded where appropriate?
- [ ] CSS-in-JS optimized (if using)?
- [ ] Minimal bundle size?
- [ ] Core Web Vitals targets met?

#### Accessibility
- [ ] Color contrast ratios meet WCAG AA (4.5:1)?
- [ ] Semantic HTML used?
- [ ] Screen reader tested?
- [ ] Keyboard navigation works?
- [ ] Focus indicators visible?
- [ ] Form labels properly associated?

---

## Quick Reference

### Color Codes

```css
/* Primary */
--blue-01: #00B0CA;
--stone-01: #F2EEEA;
--black: #000000;
--white: #FFFFFF;

/* Buttons */
--button-blue: #4C7ECF;

/* GEM - Use in context only */
--gratitude: #FFA02F;
--empathy: #00B092;
--mindfulness: #9278D1;
--emotional-literacy: #DC0451;
```

### Font Families

```css
/* Primary */
font-family: 'Public Sans', sans-serif;

/* Headings */
font-family: 'PT Serif', serif;

/* Accent/Callout */
font-family: 'Caveat', cursive;
```

### Common Patterns

```tsx
// Primary CTA Button
<Button className="bg-[#4C7ECF] text-white font-bold uppercase tracking-wide px-6 py-3 rounded-full">
  START LEARNING
</Button>

// Section with diagonal wave
<section className="relative">
  <div className="bg-blue-01 pb-20">
    {/* Content */}
  </div>
  <div className="bg-stone-01 pt-20">
    {/* Content */}
  </div>
</section>

// Card with GEM accent
<div className="bg-white rounded-2xl p-8 border-l-4 border-empathy">
  {/* Content */}
</div>
```

---

## Resources

### Apps
- Dashboard (internal): `/apps/dashboard/` — CloudWatch metrics dashboard
- Conf Uploads (internal): `/apps/conf-uploads/` — Conference leads batch importer

### Brand Assets
- Brand Guidelines PDF: `/docs/guides/trp-brand-guidelines-compressed.pdf`
- Logo files: Check with brand team
- Icon library: To be developed

### Questions?
- For **public-facing apps**: "Does this feel human, hopeful, and honest?"
- For **internal tools**: "Is this clear, functional, and easy to use?"

---

**Last Updated**: 2026-03-27
**Version**: 2.0.0
**Maintainer**: Development Team
