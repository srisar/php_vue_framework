import Vue from 'vue'
import router from './router/index'
import store from './store/index'

import App from "./App";

import axios from "axios"

const _ = require('lodash')

/* BOOTSTRAP 5 */
import '@popperjs/core'
import 'bootstrap'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'

/* local common scss */
import './assets/scss/common.scss'


/* Axios configurations */

axios.defaults.baseURL = 'http://localhost/api'
axios.defaults.headers['auth'] = store.getters.getAuthKey

/*
* Axios intercepting incoming responses, to check if it is 401,
* then route to login page, because auth token is expired, or
* not logged in
* */

axios.interceptors.response.use(undefined, (error => {
    if (error.response.status === 401) {
        store.dispatch('LOGOUT').then(() => {
            router.push('/login').then()
        })
    }

    return Promise.reject(error)
}))


/* Vue initialization */
new Vue({
    store: store,
    router: router,
    render: h => h(App)
}).$mount("#app")
