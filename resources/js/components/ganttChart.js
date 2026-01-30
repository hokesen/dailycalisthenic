// Alpine.js component for collapsible gantt chart
export default () => ({
    collapsedGroups: [],
    showAll: false,
    maxExercisesCollapsed: 5,

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

    getExerciseCount(exercises) {
        return exercises ? exercises.length : 0;
    }
});
