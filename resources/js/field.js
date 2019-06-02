Nova.booting((Vue, router, store) => {
    Vue.component('index-searchable-select', require('./components/IndexField'))
    Vue.component('detail-searchable-select', require('./components/DetailField'))
    Vue.component('form-searchable-select', require('./components/FormField'))
})
