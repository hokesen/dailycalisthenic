# Daily Calisthenics Improvement Backlog

Generated: 2026-01-20
Last Updated: 2026-01-22
Status: **15 improvements completed**, remaining items pending

## Priority Legend
- **P1**: High impact, low effort - immediate value
- **P2**: High impact, moderate effort
- **P3**: Medium impact, improves experience
- **P4**: Nice-to-have enhancements

---

## Backlog Items

### 4. [P2] Add Audio Cues for Timer Events
**Status**: BACKLOG - MAYBE LATER
**Perspective**: UX Designer / Practitioner
**Issue**: No audio feedback when timer completes or transitions between exercises/rest
**Impact**: Users don't need to watch the screen constantly
**Implementation**: Add optional sound effects for exercise start, rest start, countdown beeps

---

### 9. [P3] Add Exercise Description Tooltip/Expansion
**Status**: COMPLETED
**Perspective**: New User / Coach
**Issue**: Exercise descriptions are stored but only briefly shown during practice
**Impact**: Better exercise understanding from dashboard/template view
**Implementation**: Add info icon with tooltip or expandable section

---

### 10. [P3] Improve Empty State for New Users
**Status**: COMPLETED
**Perspective**: New User / Product Manager
**Issue**: New users see "No templates yet" with just a create button
**Impact**: Better onboarding, faster time-to-value
**Implementation**: Add suggested starter templates or quick-start guide

---

### 11. [P4] Add Practice History/Log View
**Status**: PENDING
**Perspective**: Experienced Practitioner / Product Manager
**Issue**: Sessions are saved but users can't view their history
**Impact**: Progress tracking, motivation, data-driven improvement
**Implementation**: Add activity/history page showing past sessions

---

### 12. [P4] Add Timer Sound/Vibration Options to Profile
**Status**: PENDING
**Perspective**: UX Designer / User
**Issue**: No user preferences for timer behavior
**Impact**: Personalization, quieter practice environments
**Implementation**: Add settings section in profile for audio/haptics

---

### 13. [P3] Add Exercise Video Support
**Status**: PENDING
**Perspective**: Calisthenics Coach / New User
**Issue**: Users may not know proper form for exercises
**Impact**: Better form instruction, reduced injury risk, higher engagement
**Implementation**: Add video_url field to exercises table, display video during practice timer
**Notes**: User requested - show exercise-specific video during practice (e.g., Push Ups video plays during push ups)

---

### 14. [P2] Code-Based Default Exercises with Database Override
**Status**: COMPLETED
**Perspective**: Principal Engineer / Product Manager
**Issue**: Default exercises live only in database, making updates difficult across environments
**Impact**: Easier maintenance, versioned exercise definitions, simpler deployment
**Implementation**: Define default exercises in code (seeders/config), sync to database on deploy, user exercises remain in DB only
**Notes**: User requested - allows updates to default exercises through code while preserving user customizations. Implemented via JSON file approach with 73 exercises and 20 progression paths.

---

## Completed Items

1. **Remove Emojis from UI** - Replaced emojis with SVG icons per branding guidelines
2. **Fix Brand Title** - Changed "Daily Calisthenic" to "Daily Calisthenics" (plural)
3. **Instructions During Practice** - Added expandable instructions section in timer view
4. **Exercise Category Display** - Added category badges (Push, Pull, Core, etc.) to exercise items
5. **Difficulty Level Display** - Added difficulty badges (Beginner, Intermediate, etc.) to exercises
6. **Keyboard Shortcuts** - Added Space (pause/resume), Enter (start/complete), S (skip) shortcuts
7. **Today's Practice Status** - Added "Practiced today" / "Not yet today" indicator on dashboard
8. **Exercise Description Tooltip** - Added info icon with popup for exercise descriptions
9. **SEO Meta Tags** - Added meta description and Open Graph tags for social sharing
10. **Brand Terminology Consistency** - Changed "workout" to "practice/session" in user-facing copy
11. **Difficulty Levels in Swap Dropdown** - Added difficulty level indicators to exercise swap options
12. **Organized Add Exercise Dropdown** - Grouped exercises by category with difficulty indicators
13. **Comprehensive Exercise Database** - Expanded from 38 to 73 exercises with 20 progression paths covering all major calisthenics categories
14. **Tempo and Intensity Controls** - Added tempo (slow/normal/fast/explosive) and intensity (recovery/easy/moderate/hard/maximum) options with heart rate zones
15. **Starter Templates for Onboarding** - Created 4 system templates (Quick Morning Practice, Pull Day Basics, Core & Balance, Strength Builder) with improved empty state UI

---

## Implementation Notes

- All changes should maintain backward compatibility
- Follow existing code conventions in sibling files
- Run `vendor/bin/pint --dirty` after changes
- Write tests for new functionality
- Use "practice" not "workout" in user-facing copy
