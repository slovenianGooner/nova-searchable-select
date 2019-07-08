<template>
	<span>
		<span v-if="field.isMultiple">{{resourceLabels.join(', ')}}</span>
		<span v-else>{{ field.value }}</span>
	</span>
</template>

<script>
export default {
	props: ['resourceName', 'field'],
	data() {
		return {
			resources: []
		}
	},
	mounted() {
		if (this.field.isMultiple) {
			this.getAvailableResources()
		}
	},
	computed: {
		resourceLabels() {
			let values = []
			this.resources.forEach(r => values.push(r[this.field.label]))
			return values
		}
	},
	methods: {
		getAvailableResources() {
			return Nova.request()
				.get(
					`/nova-vendor/searchable-select/${this.field.searchableResource}`,
					{
						params: {
							label: this.field.label,
							value: this.field.valueField,
							resource_ids: this.field.value
						}
					}
				)
				.then(response => {
					// Turn off initializing the existing resource after the first time
					this.resources = response.data.resources
				})
		}
	}
}
</script>
