<template>
	<default-field :field="field" :errors="errors">
		<template slot="field">
			<search-input
				v-if="!isLocked && !isReadonly"
				@input="performSearch"
				@clear="clearSelection"
				@selected="selectResource"
				:error="hasError"
				:value="selectedResource"
				:data="availableResources"
				:clearable="field.nullable"
				trackBy="value"
				searchBy="display"
				class="mb-3"
			>
				<div
					slot="default"
					v-if="selectedResource"
					class="flex items-center"
				>
					<div v-if="selectedResource.avatar" class="mr-3">
						<img
							:src="selectedResource.avatar"
							class="w-8 h-8 rounded-full block"
						/>
					</div>

					{{ selectedResource.display }}
				</div>

				<div
					slot="option"
					slot-scope="{ option, selected }"
					class="flex items-center"
				>
					<div v-if="option.avatar" class="mr-3">
						<img
							:src="option.avatar"
							class="w-8 h-8 rounded-full block"
						/>
					</div>

					{{ option.display }}
				</div>
			</search-input>

			<select-control
				v-if="isLocked || isReadonly"
				class="form-control form-select mb-3 w-full"
				:class="{ 'border-danger': hasError }"
				:dusk="field.attribute"
				@change="selectResourceFromSelectControl"
				:disabled="isLocked || isReadonly"
				:options="availableResources"
				:selected="selectedResourceId"
				label="display"
			>
				<option value="" selected :disabled="!field.nullable"
					>&mdash;</option
				>
			</select-control>
		</template>
	</default-field>
</template>

<script>
import _ from "lodash";
import { PerformsSearches, HandlesValidationErrors } from "laravel-nova";

export default {
	mixins: [PerformsSearches, HandlesValidationErrors],

	props: {
		resourceName: String,
		field: Object,
		viaResource: {},
		viaResourceId: {},
		viaRelationship: {}
	},

	data: () => ({
		availableResources: [],
		initializingWithExistingResource: false,
		selectedResource: null,
		selectedResourceId: null,
		search: ""
	}),

	/**
	 * Mount the component.
	 */
	mounted() {
		this.initializeComponent();
	},

	methods: {
		initializeComponent() {
			if (this.editingExistingResource) {
				this.initializingWithExistingResource = true;
				this.selectedResourceId = this.field.value;
			}

			if (this.shouldSelectInitialResource) {
				this.getAvailableResources().then(() =>
					this.selectInitialResource()
				);
			}

			this.field.fill = this.fill;
		},

		/**
		 * Select a resource using the <select> control
		 */
		selectResourceFromSelectControl(e) {
			this.selectedResourceId = e.target.value;
			this.selectInitialResource();
		},

		/**
		 * Fill the forms formData with details from this field
		 */
		fill(formData) {
			formData.append(
				this.field.attribute,
				this.selectedResource ? this.selectedResource.value : ""
			);
		},

		/**
		 * Get the resources that may be related to this resource.
		 */
		getAvailableResources() {
			return Nova.request()
				.get(
					`/nova-vendor/searchable-select/${
						this.field.searchableResource
					}`,
					this.queryParams
				)
				.then(response => {
					// Turn off initializing the existing resource after the first time
					this.initializingWithExistingResource = false;
					this.availableResources = response.data.resources;
				});
		},

		/**
		 * Determine if the relatd resource is soft deleting.
		 */
		determineIfSoftDeletes() {
			return storage
				.determineIfSoftDeletes(this.field.resourceName)
				.then(response => {
					this.softDeletes = response.data.softDeletes;
				});
		},

		/**
		 * Determine if the given value is numeric.
		 */
		isNumeric(value) {
			return !isNaN(parseFloat(value)) && isFinite(value);
		},

		/**
		 * Select the initial selected resource
		 */
		selectInitialResource() {
			this.selectedResource = _.find(
				this.availableResources,
				r => r.value == this.selectedResourceId
			);
		}
	},

	computed: {
		/**
		 * Determine if we are editing and existing resource
		 */
		editingExistingResource() {
			return Boolean(this.field.value);
		},

		/**
		 * Determine if we should select an initial resource when mounting this field
		 */
		shouldSelectInitialResource() {
			return Boolean(this.editingExistingResource);
		},

		/**
		 * Get the query params for getting available resources
		 */
		queryParams() {
			return {
				params: {
					search: this.search,
					label: this.field.label,
					value: this.field.valueField
				}
			};
		},

		isLocked() {
			return (
				this.viaResource == this.field.resourceName &&
				this.field.reverse
			);
		},

		isReadonly() {
			return (
				this.field.readonly ||
				_.get(this.field, "extraAttributes.readonly")
			);
		}
	}
};
</script>
