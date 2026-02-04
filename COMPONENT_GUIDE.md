# NEMSU Match - Component Guide

Visual guide to the components and pages in the NEMSU Match dating app.

## 🎨 Pages Overview

### 1. Login Page (`/nemsu/login`)

**File**: `resources/js/pages/auth/NEMSULogin.vue`

#### Design Features
```
┌─────────────────────────────────────┐
│                                     │
│         [Profile Card Left]         │
│    [Profile Card Center Featured]   │
│         [Profile Card Right]        │
│                                     │
│   Find Your Kind of Connection     │
│                                     │
│     Whether you're looking for      │
│    love, fun, or friendship...      │
│                                     │
│         [Progress Dots]             │
│                                     │
│  [Login with NEMSU Account Button] │
│                                     │
│     EXCLUSIVELY FOR NEMSU STUDENTS  │
│                                     │
└─────────────────────────────────────┘
```

#### Key Elements
- **3 Animated Profile Cards**: Floating animation with staggered delays
- **Gradient Background**: Pink to purple gradient
- **Main Heading**: "Find Your Kind of Connection"
- **Single CTA Button**: "Login with NEMSU Account" with mail icon
- **Progress Indicator**: 3 dots showing onboarding step
- **Footer Text**: NEMSU exclusivity notice

#### Colors Used
- Background: `bg-gradient-to-br from-pink-100 via-purple-50 to-pink-50`
- Cards: `from-pink-400 to-pink-500` (center), lighter variants (sides)
- Button: `from-pink-500 to-pink-600`
- Text: Gray 900 (heading), Gray 600 (body)

#### Animations
```css
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}
```

---

### 2. Profile Setup Page (`/profile/setup`)

**File**: `resources/js/pages/profile/ProfileSetup.vue`

#### 4-Step Wizard Structure
```
┌─────────────────────────────────────┐
│  Step 1 of 4              [50%]     │
│  [=========>           ]             │
│                                     │
│        [Step Icon]                  │
│       Step Title                    │
│       Description                   │
│                                     │
│    [Form Fields]                    │
│    [Form Fields]                    │
│    [Form Fields]                    │
│                                     │
│  [Back Button] [Next Button]        │
└─────────────────────────────────────┘
```

#### Step 1: Basic Information
**Icon**: User icon in pink circle

Form Fields:
- Display Name (text input) *
- Full Name (text input) *
- Date of Birth (date picker) *
- Gender (dropdown) *
  - Male
  - Female
  - Non-binary
  - Prefer not to say

#### Step 2: Academic Details
**Icon**: Graduation Cap in pink circle

Form Fields:
- Campus (dropdown) *
  - Main Campus - Tandag City
  - Cantilan Campus
  - Lianga Campus
  - Bislig Campus
- Academic Program (text input) *
- Year Level (dropdown) *
  - 1st Year
  - 2nd Year
  - 3rd Year
  - 4th Year
  - Graduate
- Favorite Courses (textarea, optional)

#### Step 3: Interests & Activities
**Icon**: Heart in pink circle

Form Fields:
- Research Interests (textarea, optional)
- Extracurricular Activities (textarea, optional)
- Hobbies & Interests (textarea, optional)
- Academic Goals (textarea, optional)

#### Step 4: Profile Photo & Bio
**Icon**: Camera in pink circle

Form Fields:
- Profile Picture (file upload, drag & drop)
  - Max 5MB
  - JPG, PNG, GIF
- Bio (textarea, required) *
  - Min 20 characters
  - Max 500 characters
  - Character counter

#### Visual Elements
- **Progress Bar**: Gradient fill showing completion percentage
- **Step Icons**: 64px circles with pink background
- **Input Fields**: Rounded-xl with focus rings
- **Buttons**: 
  - Back: Outline style with rounded-full
  - Next/Complete: Gradient pink with rounded-full
- **Animations**: Fade-in on step changes

---

### 3. Dashboard Page (`/dashboard`)

**File**: `resources/js/pages/NEMSUMatchDashboard.vue`

#### Layout Structure
```
┌─────────────────────────────────────┐
│  [NEMSU Match]    [Chat] [Settings] │
├─────────────────────────────────────┤
│                                     │
│     Welcome, [Display Name]!        │
│   Discover your perfect match       │
│                                     │
│  ┌───────────────────────────────┐  │
│  │                               │  │
│  │      [Profile Image/BG]       │  │
│  │                               │  │
│  │  ┌─────────────────────────┐  │  │
│  │  │ Name, Age               │  │  │
│  │  │ [GradCap] Program       │  │  │
│  │  │ [Pin] Campus            │  │  │
│  │  │ Bio text...             │  │  │
│  │  │ [Interest] [Tags]       │  │  │
│  │  └─────────────────────────┘  │  │
│  └───────────────────────────────┘  │
│                                     │
│    [X]      [Star]      [Heart]     │
│   Pass   Super Like     Like        │
│                                     │
│  ┌───────────────────────────────┐  │
│  │  Tips for Better Matches      │  │
│  │  • Complete your profile      │  │
│  │  • Be genuine and authentic   │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
```

#### Header Components
- **Logo**: Heart icon with "NEMSU Match" gradient text
- **Action Icons**: Message, Settings
- **User Avatar**: Circular profile picture

#### Match Card Components
- **Dimensions**: 600px height on mobile, full width
- **Background**: Gradient placeholder or uploaded image
- **Gradient Overlay**: `from-black/70 via-black/20 to-transparent`
- **Profile Info Section**:
  - Name + Age (3xl bold)
  - Academic Program with icon
  - Campus with icon
  - Bio text (paragraph)
  - Interest tags (pills with backdrop blur)

#### Action Buttons
```
┌─────────────┐  ┌──────────┐  ┌─────────────┐
│      X      │  │    ★     │  │      ♥      │
│    Pass     │  │  Super   │  │    Like     │
│  (64px)     │  │  (56px)  │  │   (64px)    │
│  White BG   │  │  Blue    │  │  Pink       │
│  Red Icon   │  │  White   │  │  White      │
└─────────────┘  └──────────┘  └─────────────┘
```

Button Specs:
- **Pass**: 64px circle, white background, red X icon
- **Super Like**: 56px circle, blue gradient, white star
- **Like**: 64px circle, pink gradient, white heart
- **Hover**: Scale(1.1)
- **Active**: Scale(0.95)

#### Tips Section
- White background with transparency
- Rounded corners
- Sparkles icon header
- Bullet points with pink dots

---

## 🎨 Design System

### Color Palette

#### Primary Colors
```css
Pink-500:  #EC4899  /* Primary actions, highlights */
Pink-600:  #DB2777  /* Hover states */
Pink-400:  #F472B6  /* Lighter accents */
Pink-100:  #FCE7F3  /* Backgrounds */
```

#### Secondary Colors
```css
Purple-500: #A855F7  /* Secondary actions */
Purple-600: #9333EA  /* Hover states */
Purple-300: #D8B4FE  /* Light accents */
Purple-50:  #FAF5FF  /* Backgrounds */
```

#### Neutral Colors
```css
Gray-900:  #111827  /* Headings */
Gray-700:  #374151  /* Body text */
Gray-600:  #4B5563  /* Secondary text */
Gray-500:  #6B7280  /* Placeholders */
Gray-400:  #9CA3AF  /* Borders */
Gray-300:  #D1D5DB  /* Dividers */
Gray-200:  #E5E7EB  /* Backgrounds */
Gray-100:  #F3F4F6  /* Light backgrounds */
```

#### Functional Colors
```css
Red-500:   #EF4444  /* Pass/reject actions */
Blue-500:  #3B82F6  /* Super like */
Green-600: #16A34A  /* Success states */
```

### Typography

#### Font Families
```css
font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 
             "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

#### Font Sizes
```css
text-xs:   0.75rem   (12px)  /* Captions, labels */
text-sm:   0.875rem  (14px)  /* Body small */
text-base: 1rem      (16px)  /* Body text */
text-lg:   1.125rem  (18px)  /* Lead text */
text-xl:   1.25rem   (20px)  /* Small headings */
text-2xl:  1.5rem    (24px)  /* Section headings */
text-3xl:  1.875rem  (30px)  /* Page headings */
```

#### Font Weights
```css
font-normal:    400  /* Body text */
font-medium:    500  /* Labels */
font-semibold:  600  /* Subheadings */
font-bold:      700  /* Headings, CTAs */
```

### Spacing

#### Common Spacing Values
```css
gap-2:  0.5rem   (8px)   /* Tight spacing */
gap-3:  0.75rem  (12px)  /* Default spacing */
gap-4:  1rem     (16px)  /* Medium spacing */
gap-6:  1.5rem   (24px)  /* Section spacing */
gap-8:  2rem     (32px)  /* Large spacing */

p-4:    1rem     (16px)  /* Default padding */
p-6:    1.5rem   (24px)  /* Medium padding */
p-8:    2rem     (32px)  /* Large padding */
p-10:   2.5rem   (40px)  /* Extra large */
```

### Border Radius

```css
rounded-xl:   0.75rem  (12px)  /* Inputs, cards */
rounded-2xl:  1rem     (16px)  /* Medium cards */
rounded-3xl:  1.5rem   (24px)  /* Large cards */
rounded-full: 9999px           /* Buttons, avatars */
```

### Shadows

```css
shadow-sm:  0 1px 2px 0 rgb(0 0 0 / 0.05)
shadow:     0 1px 3px 0 rgb(0 0 0 / 0.1)
shadow-lg:  0 10px 15px -3px rgb(0 0 0 / 0.1)
shadow-xl:  0 20px 25px -5px rgb(0 0 0 / 0.1)
shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25)
```

### Transitions

```css
transition-all:       all 300ms cubic-bezier(0.4, 0, 0.2, 1)
transition-transform: transform 300ms cubic-bezier(0.4, 0, 0.2, 1)
transition-colors:    colors 300ms cubic-bezier(0.4, 0, 0.2, 1)
```

## 🖼️ Component Library

### Button Component

#### Primary Button (Gradient)
```vue
<Button class="bg-gradient-to-r from-pink-500 to-pink-600 
               hover:from-pink-600 hover:to-pink-700 
               text-white rounded-full px-8 py-3">
    Button Text
</Button>
```

#### Secondary Button (Outline)
```vue
<Button variant="outline" 
        class="rounded-full px-8 py-3">
    Button Text
</Button>
```

#### Icon Button
```vue
<Button variant="ghost" size="icon" class="rounded-full">
    <Settings class="w-5 h-5" />
</Button>
```

### Input Component

#### Text Input
```vue
<div class="space-y-2">
    <Label for="field">Label Text *</Label>
    <Input
        id="field"
        v-model="value"
        placeholder="Placeholder text"
        class="rounded-xl"
    />
</div>
```

#### Textarea
```vue
<textarea
    v-model="value"
    class="flex min-h-[80px] w-full rounded-xl border 
           border-input bg-transparent px-3 py-2 text-base 
           shadow-sm placeholder:text-muted-foreground 
           focus-visible:outline-none focus-visible:ring-1 
           focus-visible:ring-ring"
    placeholder="Enter text..."
/>
```

#### Select/Dropdown
```vue
<select
    v-model="selected"
    class="flex h-9 w-full rounded-xl border border-input 
           bg-transparent px-3 py-1 text-base shadow-sm 
           transition-colors focus-visible:outline-none 
           focus-visible:ring-1 focus-visible:ring-ring"
>
    <option value="">Select option</option>
    <option value="1">Option 1</option>
</select>
```

### Card Component

#### Profile Card
```vue
<div class="bg-white rounded-3xl shadow-2xl overflow-hidden 
            relative h-[600px]">
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-br 
                from-pink-300 via-purple-300 to-blue-300">
        <div class="absolute inset-0 bg-gradient-to-t 
                    from-black/70 via-black/20 to-transparent" />
    </div>
    
    <!-- Content -->
    <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
        <!-- Profile info here -->
    </div>
</div>
```

#### Info Card
```vue
<div class="bg-white/60 backdrop-blur-sm rounded-2xl p-6">
    <h4 class="font-semibold text-gray-900 mb-3">Title</h4>
    <p class="text-sm text-gray-700">Content</p>
</div>
```

### Progress Component

```vue
<div class="space-y-2">
    <div class="flex justify-between text-xs text-gray-600">
        <span>Step {{ current }} of {{ total }}</span>
        <span>{{ percentage }}%</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2">
        <div
            class="bg-gradient-to-r from-pink-500 to-pink-600 
                   h-2 rounded-full transition-all duration-300"
            :style="{ width: percentage + '%' }"
        />
    </div>
</div>
```

### Icon Badge
```vue
<div class="w-16 h-16 bg-pink-100 rounded-full 
            flex items-center justify-center">
    <Heart class="w-8 h-8 text-pink-600" />
</div>
```

### Tag Component
```vue
<span class="px-3 py-1 bg-pink-100 text-pink-700 
             rounded-full text-xs font-medium">
    Tag Text
</span>
```

## 📐 Layout Patterns

### Centered Container (Mobile)
```vue
<div class="min-h-screen bg-gradient-to-br from-pink-100 
            via-purple-50 to-pink-100 flex items-center 
            justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-[2rem] shadow-2xl p-8">
            <!-- Content -->
        </div>
    </div>
</div>
```

### Header Layout
```vue
<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex justify-between items-center">
            <!-- Left side -->
            <!-- Right side -->
        </div>
    </div>
</header>
```

### Form Layout (Multi-step)
```vue
<div class="space-y-6">
    <!-- Header -->
    <div class="text-center space-y-2">
        <div class="w-16 h-16 bg-pink-100 rounded-full 
                    flex items-center justify-center mx-auto mb-3">
            <Icon class="w-8 h-8 text-pink-600" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900">Title</h2>
        <p class="text-sm text-gray-600">Description</p>
    </div>
    
    <!-- Fields -->
    <div class="space-y-4">
        <!-- Form fields -->
    </div>
    
    <!-- Actions -->
    <div class="flex gap-3 pt-4">
        <!-- Buttons -->
    </div>
</div>
```

## 🎭 Animations

### Floating Animation
```css
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.floating {
    animation: float 3s ease-in-out infinite;
}
```

### Fade In
```css
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fade-in 0.3s ease-out;
}
```

### Scale On Hover
```css
.scale-hover {
    transition: transform 300ms;
}

.scale-hover:hover {
    transform: scale(1.1);
}

.scale-hover:active {
    transform: scale(0.95);
}
```

## 📱 Responsive Utilities

### Mobile First Classes
```css
/* Base (Mobile): 375px+ */
text-base, p-4

/* Small tablets: 640px+ */
sm:text-lg, sm:p-6

/* Tablets: 768px+ */
md:text-xl, md:p-8

/* Desktop: 1024px+ */
lg:text-2xl, lg:p-10

/* Large Desktop: 1280px+ */
xl:text-3xl, xl:p-12
```

### Hide/Show on Breakpoints
```vue
<div class="block md:hidden">Mobile only</div>
<div class="hidden md:block">Desktop only</div>
```

---

## 🎨 Usage Examples

### Complete Login Button
```vue
<Button
    @click="handleLogin"
    class="w-full bg-gradient-to-r from-pink-500 to-pink-600 
           hover:from-pink-600 hover:to-pink-700 text-white 
           font-semibold py-6 rounded-full shadow-lg 
           hover:shadow-xl transition-all duration-300 text-base 
           flex items-center justify-center gap-3 group"
>
    <Mail class="w-5 h-5 group-hover:scale-110 transition-transform" />
    Login with NEMSU Account
    <ChevronRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
</Button>
```

### Complete Match Card
```vue
<div class="bg-white rounded-3xl shadow-2xl overflow-hidden 
            relative" style="height: 600px">
    <!-- Background with gradient overlay -->
    <div class="absolute inset-0 bg-gradient-to-br 
                from-pink-300 via-purple-300 to-blue-300">
        <div class="absolute inset-0 bg-gradient-to-t 
                    from-black/70 via-black/20 to-transparent" />
    </div>
    
    <!-- Profile content -->
    <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
        <h3 class="text-3xl font-bold mb-1">
            {{ name }} <span class="text-2xl font-normal">{{ age }}</span>
        </h3>
        
        <div class="flex items-center gap-2 mb-2">
            <GraduationCap class="w-4 h-4" />
            <span class="text-sm">{{ program }}</span>
        </div>
        
        <div class="flex items-center gap-2 mb-3">
            <MapPin class="w-4 h-4" />
            <span class="text-sm">{{ campus }}</span>
        </div>
        
        <p class="text-sm mb-3">{{ bio }}</p>
        
        <div class="flex flex-wrap gap-2">
            <span v-for="interest in interests"
                  class="px-3 py-1 bg-white/20 backdrop-blur-sm 
                         rounded-full text-xs font-medium">
                {{ interest }}
            </span>
        </div>
    </div>
</div>
```

---

**Component Guide Version**: 1.0  
**Last Updated**: February 3, 2026  
**For**: NEMSU Match Dating App
