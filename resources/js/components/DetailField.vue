<template>
	<div>
		<panel-item :field="field">
			<template slot="value">
				<div v-if="fieldValue" class="text-90">
					<div v-if="field.isMultiple">{{resourceLabels.join(', ')}}</div>
					<div v-else>{{ fieldValue }}</div>
				</div>
				<div v-else>&mdash;</div>
			</template>
		</panel-item>
	</div>
</template>

<script>
export default {
	props: ['resource', 'resourceName', 'resourceId', 'field'],
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
			this.resources.forEach(r => values.push(r.display))
			return values
		},
		fieldValue() {
			if (
				this.field.value === '' ||
				this.field.value === null ||
				this.field.value === undefined ||
				this.field.value === '[]'
			) {
				return false
			}

			return String(this.field.value)
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
                            labelPrefix: this.field.labelPrefix,
							value: this.field.valueField,
                            searchable: this.field.searchable == true ? 1 : 0,
							use_resource_ids: this.field.isMultiple,
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
