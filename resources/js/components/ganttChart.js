// Alpine.js component for collapsible gantt chart
export default () => ({
    collapsedGroups: [],
    expandedGroups: [],
    maxExercisesCollapsed: 5,
    selectedCell: null,
    showDetailPanel: false,

    init() {
        // Restore collapsed state from localStorage
        const saved = localStorage.getItem('gantt_collapsed_groups');
        if (saved) {
            try {
                this.collapsedGroups = JSON.parse(saved);
            } catch (e) {
                console.error('Failed to parse collapsed groups:', e);
                this.collapsedGroups = [];
            }
        }

        // Restore expanded state from localStorage
        const savedExpanded = localStorage.getItem('gantt_expanded_groups');
        if (savedExpanded) {
            try {
                this.expandedGroups = JSON.parse(savedExpanded);
            } catch (e) {
                console.error('Failed to parse expanded groups:', e);
                this.expandedGroups = [];
            }
        }
    },

    toggleGroup(groupName) {
        const index = this.collapsedGroups.indexOf(groupName);
        if (index > -1) {
            this.collapsedGroups.splice(index, 1);
        } else {
            this.collapsedGroups.push(groupName);
        }

        // Save to localStorage
        localStorage.setItem('gantt_collapsed_groups', JSON.stringify(this.collapsedGroups));
    },

    isGroupCollapsed(groupName) {
        return this.collapsedGroups.includes(groupName);
    },

    toggleExpanded(groupName) {
        const index = this.expandedGroups.indexOf(groupName);
        if (index > -1) {
            this.expandedGroups.splice(index, 1);
        } else {
            this.expandedGroups.push(groupName);
        }

        // Save to localStorage
        localStorage.setItem('gantt_expanded_groups', JSON.stringify(this.expandedGroups));
    },

    isGroupExpanded(groupName) {
        return this.expandedGroups.includes(groupName);
    },

    shouldShowExercise(groupName, index, totalCount) {
        // Always show if expanded or total count is small
        if (this.isGroupExpanded(groupName) || totalCount <= this.maxExercisesCollapsed) {
            return true;
        }
        // Otherwise only show first maxExercisesCollapsed exercises
        return index < this.maxExercisesCollapsed;
    },

    getHiddenCount(groupName, totalCount) {
        if (this.isGroupExpanded(groupName) || totalCount <= this.maxExercisesCollapsed) {
            return 0;
        }
        return totalCount - this.maxExercisesCollapsed;
    },

    getExerciseCount(exercises) {
        return exercises ? exercises.length : 0;
    },

    openDetail(exerciseName, exerciseId, seconds, date, progressionPath = null, progressionLevel = null) {
        if (seconds > 0) {
            this.selectedCell = {
                exerciseName,
                exerciseId,
                seconds,
                date,
                progressionPath,
                progressionLevel
            };
            this.showDetailPanel = true;
        }
    },

    closeDetail() {
        this.showDetailPanel = false;
        setTimeout(() => {
            this.selectedCell = null;
        }, 300);
    },

    formatDuration(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        if (mins > 0) {
            return secs > 0 ? `${mins}m ${secs}s` : `${mins}m`;
        }
        return `${secs}s`;
    }
});
