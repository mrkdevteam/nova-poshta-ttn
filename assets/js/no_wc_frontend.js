//dev
//import Vue from "https://cdn.jsdelivr.net/npm/vue@2.6.11/dist/vue.esm.browser.js";
//prod
import Vue from "https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.11/vue.esm.browser.min.js";

let dropdown = Vue.component("Dropdown", {
  template: `
  <div class="dropdown" v-if="options">
  <!-- Dropdown Input -->
  <input
    :id="inputId"
    autocomplete="off"
    class="dropdown-input"
    :name="name"
    @focus="showOptions()"
    @blur="exit()"
    @keyup="keyMonitor"
    v-model="searchFilter"
    :disabled="disabled"
    :placeholder="placeholder" 
  />

  <!-- Dropdown Menu -->
  <div class="dropdown-content" v-show="optionsShown">
    <div
      class="dropdown-item"
      @mousedown="selectOption(option)"
      v-for="(option, index) in filteredOptions"
      :key="index"
    >{{ option.name || option.id || "-" }}</div>
  </div>
</div>`,
  props: {
    startswith: {
      required: false,
    },
    inputId: {
      type: String,
      required: false,
    },
    name: {
      type: String,
      required: false,
      default: "dropdown",
      note: "Input name",
    },
    options: {
      type: Array,
      required: true,
      note: "Options of dropdown. An array of options with id and name",
    },
    placeholder: {
      type: String,
      required: false,
      default: "Please select an option",
      note: "Placeholder of dropdown",
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false,
      note: "Disable the dropdown",
    },
    maxItem: {
      type: Number,
      required: false,
      default: 16,
      note: "Max items showing",
    },
  },
  data() {
    return {
      selected: {},
      inputvalue: "",
      optionsShown: false,
      searchFilter: "",
    };
  },
  created() {
    this.$emit("selected", this.selected);
  },
  computed: {
    filteredOptions() {
      const filtered = [];
      let regOption = new RegExp(this.searchFilter, "i");
      if (this.startswith) {
        regOption = new RegExp("^" + this.searchFilter, "i");
      }

      for (const option of this.options) {
        if (this.searchFilter.length < 1 || option.name.match(regOption)) {
          if (filtered.length < this.maxItem) filtered.push(option);
        }
      }
      return filtered;
    },
  },
  methods: {
    selectOption(option) {
      this.selected = option;
      this.inputvalue = option.name;
      this.searchFilter = option.name;
      this.optionsShown = false;
      this.searchFilter = this.selected.name;
      this.$emit("selected", this.selected);
    },
    showOptions() {
      if (!this.disabled) {
        this.searchFilter = "";
        this.optionsShown = true;
      }
    },
    exit() {
      if (!this.selected.id) {
        //this.selected = {};
        //this.searchFilter = "";
      } else {
        this.searchFilter = this.selected.name;
      }
      this.$emit("selected", this.selected);
      this.optionsShown = false;
    },
    // Selecting when pressing Enter
    keyMonitor: function (event) {
      if (event.key === "Enter" && this.filteredOptions[0])
        this.selectOption(this.filteredOptions[0]);
    },
  },
  watch: {
    searchFilter() {
      if (this.filteredOptions.length === 0) {
        this.selected = {};
      } else {
        this.selected = this.filteredOptions[0];
      }
      this.$emit("filter", this.searchFilter);
    },
  },
});

let formgroup = Vue.component("formgroup", {
  template: `
  <div>
    <div>
      <label for="npcity"></label>
      <DropDown
        inputId="npcity"
        :options="npcities"
        v-on:selected="npCitySelection"
        :disabled="false"
        startswith="true" 
        placeholder="Почніть вводити місто"
      />
    </div>
    <div v-if="selectedCity">
      <label for="npwarehouses"></label>
      <DropDown
        inputId="npwarehouses"
        :options="npwarehouses"
        v-on:selected="npWarehouseSelection"
        :disabled="false"
        placeholder="Почніть вводити відділення"
      />
      <input type="hidden" name="delivery_details" v-model="deliveryDetail" required />
    </div>
  </div>`,
  props: {
    apikey: String,
  },
  components: { DropDown: dropdown },
  data() {
    return {
      loadingWarehouses: false,
      loadingCities: false,
      cityName: "",
      warehousename: "",
      citieslist: [],
      warehouseslist: [],
      selectedCity: null,
    };
  },
  methods: {
    getNpCities() {
      axios({
        method: "get",
        url: "/wp-json/mrk/v1/cities/",
        data: {
          apiKey: this.apikey,
          modelName: "Address",
          calledMethod: "getCities",
        },
      }).then((response) => (this.citieslist = JSON.parse(response.data)));
    },
    getNpWarehouses() {
      axios({
        method: "post",
        url: "/wp-json/mrk/v1/get_city_warehouses",
        data: {
          id: this.selectedCity,
        },
      }).then((response) => (this.warehouseslist = JSON.parse(response.data)));
    },
    npCitySelection(city) {
      this.selectedCity = city.id;
      this.cityName = city.name;
      if (this.selectedCity) {
        this.getNpWarehouses();
      }
    },
    npWarehouseSelection(warehouse) {
      if (warehouse.name) {
        this.warehousename = warehouse.name;
      }
    },
  },
  mounted() {
    this.getNpCities();
  },
  computed: {
    npcities() {
      return this.citieslist.map((obj) => {
        return { id: obj.ref, name: obj.description, ru: obj.description };
      });
    },
    npwarehouses() {
      return this.warehouseslist.map((obj) => {
        return { id: obj.description, name: obj.description };
      });
    },
    deliveryDetail() {
      if (this.cityName && this.warehousename) {
        return `${this.cityName}, ${this.warehousename}`;
      } else {
        return "";
      }
    },
  },
});

new Vue({
  el: "#vue-nova-poshta",
  components: { formgroup },
});
