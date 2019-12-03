<template>
  <default-field :field="field" :errors="errors">
    <template slot="field">
      <div v-if="selectedResources.length" class="mb-3 searchable-select__selected-resources">
        <div
          class="shadow-md mr-3 inline-block rounded-lg bg-gray-400 mb-3"
          v-for="resource, index in selectedResources"
          :key="index"
          :data="resource"
        >
          <div class="flex items-center px-4 py-1">
            <div class="mr-2">{{resource.display}}</div>
            <div @click="removeResource(resource)" class="cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                <path
                  class="heroicon-ui"
                  d="M8 6V4c0-1.1.9-2 2-2h4a2 2 0 0 1 2 2v2h5a1 1 0 0 1 0 2h-1v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8H3a1 1 0 1 1 0-2h5zM6 8v12h12V8H6zm8-2V4h-4v2h4zm-4 4a1 1 0 0 1 1 1v6a1 1 0 0 1-2 0v-6a1 1 0 0 1 1-1zm4 0a1 1 0 0 1 1 1v6a1 1 0 0 1-2 0v-6a1 1 0 0 1 1-1z"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <input-searchable-select
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
        <div slot="loading" class="bg-none py-3 overflow-hidden w-full" v-if="searching">
          <loader class="text-60" width="40" />
        </div>

        <div slot="default" v-if="selectedResource" class="flex items-center">
          <div v-if="selectedResource.avatar" class="mr-3">
            <img :src="selectedResource.avatar" class="w-8 h-8 rounded-full block" />
          </div>
          {{ selectedResource.display }}
        </div>

        <div slot="option" slot-scope="{ option, selected }" class="flex items-center">
          <div v-if="option.avatar" class="mr-3">
            <img :src="option.avatar" class="w-8 h-8 rounded-full block" />
          </div>
          {{ option.display }}
        </div>
      </input-searchable-select>

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
        <option value selected :disabled="!field.nullable">&mdash;</option>
      </select-control>
    </template>
  </default-field>
</template>

<script>
import _ from 'lodash'
import { PerformsSearches, HandlesValidationErrors } from 'laravel-nova'

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
    selectedResources: [],
    selectedResource: null,
    selectedResourceId: null,
    search: '',
    searching: false
  }),

  /**
   * Mount the component.
   */
  mounted() {
    this.initializeComponent()
    this.$nextTick(this.loadResourcesOnNew);
  },

  methods: {
    existingResourceIndex(value) {
      let index = -1
      this.selectedResources.forEach((r, i) => {
        if (r[this.field.valueField] == value[this.field.valueField]) {
          index = i
        }
      })

      return index
    },
    removeResource(value) {
      this.selectedResources.splice(this.existingResourceIndex(value), 1)
    },
    selectResource(value) {
      if (this.field.isMultiple) {
        if (this.existingResourceIndex(value) > -1) {
          this.removeResource(value)
        } else {
          this.selectedResources.push(value)
        }
        this.selectedResource = null
        this.selectedResourceId = null
        this.search = ''
      } else {
        this.selectedResource = value
        this.selectedResourceId = value[this.field.valueField]
      }
    },

    initializeComponent() {
      if (this.editingExistingResource) {
        this.initializingWithExistingResource = true
        this.selectedResourceId = this.field.value
      }

      if (this.shouldSelectInitialResource) {
        this.getAvailableResources('', true, null).then(() =>
          this.selectInitialResource()
        )
      }

      this.field.fill = this.fill
    },

    /**
     * Select a resource using the <select> control
     */
    selectResourceFromSelectControl(e) {
      this.selectedResourceId = e.target.value
      this.selectInitialResource()
    },

    /**
     * Fill the forms formData with details from this field
     */
    fill(formData) {
      if (this.field.isMultiple) {
        formData.append(
          this.field.attribute,
          JSON.stringify(this.selectedResourcesIds)
        )
      } else {
        formData.append(
          this.field.attribute,
          this.selectedResource ? this.selectedResource.value : ''
        )
      }
    },

    /**
     * Get the resources that may be related to this resource.
     */
    getAvailableResources(query, use_resource_ids, max) {
      this.searching = true
      this.availableResources = []

      let params = this.queryParams
      if (max !== null) {
        params.params.max = this.field.max
      }

      if (this.field.isMultiple) {
        let resourceIds = []
        if (this.selectedResourcesIds.length > 0) {
          resourceIds = this.selectedResourcesIds
        } else if (this.selectedResourceId !== null) {
          resourceIds = JSON.parse(this.selectedResourceId)
        }

        params.params.resource_ids = JSON.stringify(resourceIds)
        if (use_resource_ids !== undefined) {
          params.params.use_resource_ids = true
        } else {
          params.params.ignore_resource_ids = true
        }
      } else {
        if (this.selectedResourceId !== null) {
          params.params.resource_ids = JSON.stringify([this.selectedResourceId])
          if (use_resource_ids) {
            params.params.use_resource_ids = true
          }
        }
      }
      return Nova.request()
        .get(
          `/nova-vendor/searchable-select/${this.field.searchableResource}`,
          params
        )
        .then(response => {
          // Turn off initializing the existing resource after the first time
          this.initializingWithExistingResource = false
          this.availableResources = response.data.resources
          this.searching = false
        })
    },

    /**
     * Determine if the relatd resource is soft deleting.
     */
    determineIfSoftDeletes() {
      return storage
        .determineIfSoftDeletes(this.field.resourceName)
        .then(response => {
          this.softDeletes = response.data.softDeletes
        })
    },

    /**
     * Determine if the given value is numeric.
     */
    isNumeric(value) {
      return !isNaN(parseFloat(value)) && isFinite(value)
    },

    /**
     * Select the initial selected resource
     */
    selectInitialResource() {
      if (this.field.isMultiple) {
        this.availableResources.forEach(r => {
          if (this.field.value.includes(r[this.field.valueField])) {
            this.selectedResources.push(r)
          }
        })
      } else {
        this.selectedResource = _.find(
          this.availableResources,
          r => r[this.field.valueField] == this.selectedResourceId
        )
      }
    },

    /**
     * When not editing and loadsResourcesOnNew is enable we preload a list of resources
     */
    loadResourcesOnNew() {
      if ('loadResourcesOnNew' in this.field && this.field.loadResourcesOnNew && !this.editingExistingResource) {
        this.search = ''
        this.getAvailableResources('')
      }
    }
  },

  computed: {
    selectedResourcesIds() {
      let ids = []
      this.selectedResources.forEach(r => {
        ids.push(r[this.field.valueField])
      })

      return ids
    },
    /**
     * Determine if we are editing and existing resource
     */
    editingExistingResource() {
      return Boolean(this.field.value)
    },

    /**
     * Determine if we should select an initial resource when mounting this field
     */
    shouldSelectInitialResource() {
      return Boolean(this.editingExistingResource)
    },

    /**
     * Get the query params for getting available resources
     */
    queryParams() {
      return {
        params: {
          search: this.search,
          searchable: this.field.searchable == true ? 1 : 0,
          label: this.field.label,
          labelPrefix: this.field.labelPrefix,
          value: this.field.valueField
        }
      }
    },

    isLocked() {
      return this.viaResource == this.field.resourceName && this.field.reverse
    },

    isReadonly() {
      return (
        this.field.readonly || _.get(this.field, 'extraAttributes.readonly')
      )
    }
  }
}
</script>
