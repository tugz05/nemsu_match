# NEMSU Match - Profile Setup Testing Checklist

## 🧪 Quick Testing Guide

### Prerequisites
✅ Dev server running (`composer run dev`)
✅ Database migrated
✅ Suggestions seeded
✅ Logged in with NEMSU account

---

## Test Flow

### 🎯 **Initial Load**
- [ ] Navigate to `/profile/setup`
- [ ] Animated hearts floating in background ❤️
- [ ] Header with sparkles visible
- [ ] White card with backdrop blur
- [ ] Progress bar at 0% (Step 1 of 4)

---

### 📝 **Step 1: Basic Information**

**Visual Checks:**
- [ ] User icon bouncing slowly
- [ ] All input fields have blue bullet points
- [ ] Inputs have rounded-xl borders
- [ ] Hover on input fields scales slightly

**Functionality:**
1. **Display Name:**
   - [ ] Type your display name
   - [ ] Field has proper focus ring

2. **Full Name:**
   - [ ] Pre-filled from Google account
   - [ ] Can be edited

3. **Date of Birth:**
   - [ ] Label shows "(MM/DD/YYYY)"
   - [ ] Native date picker appears
   - [ ] Cannot select future dates

4. **Gender Identity:**
   - [ ] Dropdown has 10 options
   - [ ] Includes LGBTQ+ options
   - [ ] Options: Male, Female, Non-binary, Transgender, Genderqueer, Genderfluid, Agender, Two-Spirit, Prefer not to say, Prefer to self-describe

**Validation:**
- [ ] Next button DISABLED if any field empty
- [ ] Next button ENABLED when all filled
- [ ] Click Next → Progress updates to 25%

---

### 🎓 **Step 2: Academic Details**

**Visual Checks:**
- [ ] Graduation cap icon bouncing
- [ ] Back button appears (rounded outline)
- [ ] Smooth slide-in animation
- [ ] Progress bar animates to 25%

**Functionality:**
1. **Campus:**
   - [ ] Dropdown has 8 campuses
   - [ ] Options: Tandag, Bislig, Tagbina, Lianga, Cagwait, San Miguel, Marihatag Offsite, Cantilan
   - [ ] Select your campus

2. **Academic Program:**
   - [ ] Label shows "(Start typing for suggestions)"
   - [ ] Type "computer" → Autocomplete dropdown appears
   - [ ] Suggestions: "BS Computer Science", "BS Information Technology"
   - [ ] Click suggestion → Fills input
   - [ ] Dropdown has graduation cap icons on hover
   - [ ] Dropdown has blue border and shadow

3. **Year Level:**
   - [ ] 5 options: 1st Year through Graduate
   - [ ] Select your year

4. **Favorite Courses (Tags):**
   - [ ] Tag input with border
   - [ ] Type "Data" → Autocomplete appears
   - [ ] Suggestions: "Data Structures and Algorithms", "Database Management", "Data Science"
   - [ ] Press Enter → Tag appears with blue gradient
   - [ ] Tag has X button
   - [ ] Click X → Tag removed with animation
   - [ ] Add multiple tags (try pressing comma instead of Enter)
   - [ ] Counter shows "X/10 tags"

**Validation:**
- [ ] Next button DISABLED if campus, program, or year empty
- [ ] Courses are optional
- [ ] Click Next → Progress updates to 50%

---

### ❤️ **Step 3: Interests**

**Visual Checks:**
- [ ] Heart icon bouncing
- [ ] 4 tag input fields
- [ ] Smooth slide-in animation
- [ ] Progress bar animates to 50%

**Functionality:**
1. **Research Interests:**
   - [ ] Type "machine" → Autocomplete shows "Machine Learning", "Artificial Intelligence"
   - [ ] Add tags
   - [ ] Maximum 10 tags

2. **Extracurricular Activities:**
   - [ ] Type "basket" → Shows "Basketball"
   - [ ] Type "student" → Shows "Student Council"
   - [ ] Add multiple activities
   - [ ] Maximum 10 tags

3. **Hobbies & Interests:**
   - [ ] Type "reading" → Autocomplete
   - [ ] Type "gaming" → Autocomplete
   - [ ] Type "cooking" → Autocomplete
   - [ ] Add various hobbies
   - [ ] Maximum 15 tags

4. **Academic Goals:**
   - [ ] Type "graduate" → Shows "Graduate with honors"
   - [ ] Type "publish" → Shows "Publish research"
   - [ ] Add goals
   - [ ] Maximum 8 tags

**Tag Behavior:**
- [ ] Tags appear with scale-in animation
- [ ] Blue to cyan gradient on tags
- [ ] White text
- [ ] X button has hover effect
- [ ] Autocomplete closes after selection
- [ ] Can backspace to delete last tag when input is empty

**Validation:**
- [ ] All fields are optional
- [ ] Next button ALWAYS enabled
- [ ] Click Next → Progress updates to 75%

---

### 📸 **Step 4: Profile & Bio**

**Visual Checks:**
- [ ] Camera icon bouncing
- [ ] Progress bar at 75%
- [ ] Upload area or preview visible

**Functionality - Before Upload:**
1. **Upload Area:**
   - [ ] Dashed border box
   - [ ] Camera icon in gradient circle
   - [ ] "Click to upload profile picture" text
   - [ ] "JPG, PNG or GIF (Max 5MB)" subtitle
   - [ ] Hover changes border color to blue

2. **Click Upload Area:**
   - [ ] File picker opens
   - [ ] Select an image (JPG/PNG)

**Functionality - After Upload:**
1. **Circular Preview:**
   - [ ] Image appears in 140x140px circle
   - [ ] Border with gradient effect
   - [ ] Ring around the circle (blue-100)
   - [ ] Scale-in animation
   - [ ] Camera button at bottom-right
   - [ ] Camera button has blue gradient
   - [ ] Camera button scales on hover

2. **Re-upload:**
   - [ ] Click camera button
   - [ ] File picker opens again
   - [ ] New image replaces old preview

3. **Bio:**
   - [ ] Large textarea (140px height)
   - [ ] Placeholder: "Write a short bio..."
   - [ ] Label shows "(minimum 10 characters)"
   - [ ] Type 9 characters → Counter shows "9/500" in gray
   - [ ] Type 10+ characters → Counter turns green with checkmark ✓
   - [ ] Max 500 characters
   - [ ] Textarea has focus ring
   - [ ] No resize handle (resize-none)

**Validation:**
- [ ] Complete Profile button DISABLED if bio < 10 characters
- [ ] Complete Profile button ENABLED when bio ≥ 10
- [ ] Button has Sparkles icon
- [ ] Button has animated gradient
- [ ] Button text: "Complete Profile"

**Submission:**
1. **Click Complete Profile:**
   - [ ] Spinner appears on button
   - [ ] Button disabled during submission
   - [ ] Progress bar reaches 100%

2. **Success:**
   - [ ] Redirects to `/dashboard`
   - [ ] Success message appears
   - [ ] Profile picture visible (if uploaded)

---

## 🎨 Animation Checks

### **Hearts:**
- [ ] 8 hearts floating
- [ ] Different sizes
- [ ] Float from bottom to top
- [ ] Rotate as they float
- [ ] Semi-transparent
- [ ] Blue gradient

### **Progress Bar:**
- [ ] Smooth width transitions
- [ ] Blue-cyan gradient
- [ ] Background shimmer effect
- [ ] Percentage updates

### **Step Icons:**
- [ ] Bounce animation (slow)
- [ ] Blue-cyan gradient background
- [ ] Shadow effect

### **Inputs:**
- [ ] Scale on hover (1.01)
- [ ] Blue ring on focus
- [ ] Smooth transitions

### **Buttons:**
- [ ] Scale on hover (1.05)
- [ ] Shadow elevation
- [ ] Gradient animation
- [ ] Disabled state (opacity 50%)

### **Tags:**
- [ ] Scale-in when added
- [ ] Smooth removal
- [ ] Hover effect on X

### **Autocomplete:**
- [ ] Fade-in from top
- [ ] Bullet appears on hover
- [ ] Smooth hover state

---

## 🗄️ Database Verification

### **After Submission, Check Database:**

1. **users table:**
   ```sql
   SELECT * FROM users WHERE email = 'your@nemsu.edu.ph';
   ```
   - [ ] `display_name` filled
   - [ ] `fullname` filled
   - [ ] `campus` filled
   - [ ] `academic_program` filled
   - [ ] `year_level` filled
   - [ ] `profile_picture` path (if uploaded)
   - [ ] `courses` JSON array
   - [ ] `research_interests` JSON array
   - [ ] `extracurricular_activities` JSON array
   - [ ] `academic_goals` JSON array
   - [ ] `interests` JSON array
   - [ ] `bio` filled
   - [ ] `date_of_birth` filled
   - [ ] `gender` filled
   - [ ] `profile_completed` = 1

2. **academic_programs table:**
   ```sql
   SELECT * FROM academic_programs WHERE name = 'YOUR_PROGRAM';
   ```
   - [ ] Entry exists
   - [ ] `usage_count` incremented

3. **courses table:**
   ```sql
   SELECT * FROM courses WHERE name IN ('YOUR_COURSES');
   ```
   - [ ] Entries exist for each course
   - [ ] `usage_count` incremented

4. **interests table:**
   ```sql
   SELECT * FROM interests WHERE category = 'research';
   SELECT * FROM interests WHERE category = 'extracurricular';
   SELECT * FROM interests WHERE category = 'hobby';
   SELECT * FROM interests WHERE category = 'academic_goal';
   ```
   - [ ] Entries exist for each interest
   - [ ] Correct categories
   - [ ] `usage_count` incremented

---

## 🐛 Edge Cases to Test

### **Navigation:**
- [ ] Click Back from Step 2 → Returns to Step 1 with data preserved
- [ ] Click Back from Step 3 → Returns to Step 2 with data preserved
- [ ] Click Back from Step 4 → Returns to Step 3 with data preserved
- [ ] Progress bar updates correctly on back navigation

### **Validation Errors:**
1. Try to submit with empty bio:
   - [ ] Button stays disabled
   - [ ] No submission occurs

2. Type bio with exactly 9 characters:
   - [ ] Counter shows "9/500" in gray
   - [ ] No checkmark
   - [ ] Button disabled

3. Type bio with exactly 10 characters:
   - [ ] Counter shows "10/500" in green
   - [ ] Checkmark appears
   - [ ] Button enabled

### **Tags:**
1. Add 10 courses:
   - [ ] 10th tag adds successfully
   - [ ] Counter shows "10/10"
   - [ ] Can still type to search

2. Try to add 11th course:
   - [ ] Tag doesn't add
   - [ ] Stays at 10/10

3. Add tag with comma:
   - [ ] Type "Test," → Tag added

4. Add tag with Enter:
   - [ ] Type "Test" + Enter → Tag added

5. Backspace on empty input:
   - [ ] Last tag removes

### **Autocomplete:**
1. Type very fast:
   - [ ] Suggestions still load correctly
   - [ ] No flickering

2. Type then quickly blur:
   - [ ] Dropdown closes after short delay

3. Type invalid query:
   - [ ] No dropdown appears
   - [ ] No errors

### **File Upload:**
1. Upload very large file (> 5MB):
   - [ ] Validation error on submission

2. Upload non-image file:
   - [ ] File picker restricts to images

3. Upload then change image:
   - [ ] Preview updates correctly
   - [ ] Old file replaced

---

## ✅ Success Criteria

All checkboxes above should be checked ✓

### **User Experience:**
- Smooth, professional animations
- Intuitive navigation
- Helpful autocomplete
- Clear validation feedback
- Beautiful design

### **Functionality:**
- All fields save correctly
- Tags stored as arrays
- Suggestions populate database
- Redirect works
- Images upload properly

### **Performance:**
- Fast autocomplete (< 200ms)
- Smooth animations (60fps)
- Quick page load (< 1s)
- Efficient database queries

---

## 🚨 Common Issues & Solutions

### **Autocomplete not working:**
- Check dev server is running
- Verify database has seeded data
- Check browser console for errors
- Verify routes are registered

### **Tags not appearing:**
- Check TagsInput component is imported
- Verify v-model binding
- Check browser console for errors

### **Images not uploading:**
- Verify storage link: `php artisan storage:link`
- Check `public/storage` folder exists
- Verify file permissions

### **Hearts not floating:**
- Check CSS is compiled
- Verify animations aren't disabled by browser
- Check keyframes in component

### **Progress bar stuck:**
- Verify currentStep is updating
- Check progressPercentage computed property
- Inspect CSS transition property

---

## 📸 Screenshot Checklist

Capture screenshots of:
- [ ] Step 1 (empty state)
- [ ] Step 1 (filled)
- [ ] Step 2 with autocomplete dropdown
- [ ] Step 2 with tags added
- [ ] Step 3 with multiple tags
- [ ] Step 4 with circular preview
- [ ] Step 4 with valid bio
- [ ] Error state
- [ ] Mobile view

---

## 🎉 Final Verification

- [ ] Complete entire flow from login to dashboard
- [ ] Create second profile to test autocomplete suggestions
- [ ] Verify suggestions from first profile appear for second user
- [ ] Check all data in database
- [ ] Test on different browsers (Chrome, Firefox, Edge)
- [ ] Test on mobile device
- [ ] Test with screen reader (basic accessibility)

**When all tests pass: Your Profile Setup is production-ready!** 🚀✨
