<!--begin::Third Party Plugin(OverlayScrollbars)-->
<script
    src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
    crossorigin="anonymous"
    ></script>
<!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
<script
    src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    crossorigin="anonymous"
    ></script>
<!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
    crossorigin="anonymous"
    ></script>
<!--end::Required Plugin(Bootstrap 5)--><!--begin::Admin Theme Core-->
<script src="{{ asset('admin/js/adminlte.js') }}"></script>
<!--end::Admin Theme Core--><!--begin::OverlayScrollbars Configure-->
<script>
    const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
    const Default = {
      scrollbarTheme: 'os-theme-light',
      scrollbarAutoHide: 'leave',
      scrollbarClickScroll: true,
    };
    document.addEventListener('DOMContentLoaded', function () {
      const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
      if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
        OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
          scrollbars: {
            theme: Default.scrollbarTheme,
            autoHide: Default.scrollbarAutoHide,
            clickScroll: Default.scrollbarClickScroll,
          },
        });
      }
    });
</script>
<!--end::OverlayScrollbars Configure-->
<!-- OPTIONAL SCRIPTS -->
<!-- apexcharts -->
<script
    src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8="
    crossorigin="anonymous"
    ></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof ApexCharts === 'undefined' || !window.adminDashboardCharts) {
        return;
      }

      const chartConfig = window.adminDashboardCharts;

      const renderChart = function(selector, options) {
        const element = document.querySelector(selector);
        if (!element) {
          return;
        }

        const chart = new ApexCharts(element, options);
        chart.render();
      };

      if (chartConfig.trend) {
        renderChart('#business-trend-chart', {
          series: chartConfig.trend.series,
          chart: {
            type: 'area',
            height: 340,
            toolbar: {
              show: false,
            },
          },
          dataLabels: {
            enabled: false,
          },
          stroke: {
            curve: 'smooth',
            width: 3,
          },
          fill: {
            type: 'gradient',
            gradient: {
              shadeIntensity: 1,
              opacityFrom: 0.28,
              opacityTo: 0.04,
              stops: [0, 90, 100],
            },
          },
          colors: ['#2d6fa3', '#5bb8ab', '#f0b24f'],
          legend: {
            position: 'top',
            horizontalAlign: 'left',
          },
          xaxis: {
            categories: chartConfig.trend.labels,
          },
          grid: {
            borderColor: 'rgba(15, 23, 42, 0.08)',
            strokeDashArray: 4,
          },
          tooltip: {
            shared: true,
            intersect: false,
          },
        });
      }

      if (chartConfig.categories) {
        renderChart('#category-volume-chart', {
          series: [
            {
              name: 'Products',
              data: chartConfig.categories.series,
            },
          ],
          chart: {
            type: 'bar',
            height: 300,
            toolbar: {
              show: false,
            },
          },
          plotOptions: {
            bar: {
              horizontal: true,
              borderRadius: 6,
              barHeight: '56%',
            },
          },
          dataLabels: {
            enabled: false,
          },
          colors: ['#2d6fa3'],
          xaxis: {
            categories: chartConfig.categories.labels,
          },
          grid: {
            borderColor: 'rgba(15, 23, 42, 0.08)',
          },
        });
      }

      if (chartConfig.reviewHealth) {
        renderChart('#review-health-chart', {
          series: chartConfig.reviewHealth.series,
          chart: {
            type: 'donut',
            height: 260,
          },
          labels: chartConfig.reviewHealth.labels,
          colors: ['#5bb8ab', '#f0b24f'],
          legend: {
            position: 'bottom',
          },
          stroke: {
            width: 0,
          },
          dataLabels: {
            enabled: true,
            formatter: function (value) {
              return Math.round(value) + '%';
            },
          },
        });
      }

      if (chartConfig.catalogMix) {
        renderChart('#catalog-mix-chart', {
          series: chartConfig.catalogMix.series,
          chart: {
            type: 'donut',
            height: 300,
          },
          labels: chartConfig.catalogMix.labels,
          colors: ['#2d6fa3', '#50657c', '#f0b24f', '#cf7147', '#5bb8ab'],
          legend: {
            position: 'bottom',
          },
          stroke: {
            width: 0,
          },
          dataLabels: {
            enabled: true,
            formatter: function (value) {
              return Math.round(value) + '%';
            },
          },
        });
      }
    });
</script>
<!-- jQuery -->
<script src="{{ url('admin/js/jquery-3.7.1.min.js') }}"></script>

<!-- jQuery UI -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Custom Script -->
<script src="{{ url('admin/js/custom.js') }}"></script>

<!-- DataTable CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.dataTables.min.css">

<!-- ColReorder CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.6.2/css/colReorder.dataTables.min.css">

<!-- Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="{{ asset('admin/css/admin-tables.css') }}">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- ColReorder JS -->
<script src="https://cdn.datatables.net/colreorder/1.6.2/js/dataTables.colReorder.min.js"></script>

<!-- Buttons Extension -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>

<!-- Column Visibility -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>

<script>
    $(document).ready(function() {
      $("#subadmins").DataTable();
      if ($("#brands").length > 0) {
        $("#brands").DataTable({
          order: [[0, "desc"]],
          pageLength: 10,
          columnDefs: [
            { targets: [4], orderable: false, searchable: false }
          ],
          language: {
            search: "Search brands:",
            lengthMenu: "Show _MENU_ brands",
            info: "Showing _START_ to _END_ of _TOTAL_ brands"
          }
        });
      }

      const tableConfig = [
        {
          id: "categories",
          savedOrder: {!!json_encode($categoriesSavedOrder ?? null)!!},
          hiddenCols: {!!json_encode($categoriesHiddenCols ?? null)!!},
          tableName: "categories"
        },
        {
          id: "products",
          savedOrder: {!!json_encode($productsSavedOrder ?? null)!!},
          hiddenCols: {!!json_encode($productsHiddenCols ?? null)!!},
          tableName: "products",
          pageLength: 10,
          nonOrderableTargets: [6],
          language: {
            search: "Search products:",
            lengthMenu: "Show _MENU_ products",
            info: "Showing _START_ to _END_ of _TOTAL_ products"
          }
        },
        {
            id: "filters",
            savedOrder: {!!json_encode($filtersSavedOrder ?? null)!!},
            hiddenCols: {!!json_encode($filtersHiddenCols ?? null)!!},
            tableName: "filters"
        },
        {
            id: "filters_values",
            savedOrder: {!!json_encode($filtersValuesSavedOrder ?? null)!!},
            hiddenCols: {!!json_encode($filtersValuesHiddenCols ?? null)!!},
            tableName: "filters_values"
        },
        {
            id: "coupons",
            savedOrder: {!!json_encode($couponsSavedOrder ?? null)!!},
            hiddenCols: {!!json_encode($couponsHiddenCols ?? null)!!},
            tableName: "coupons",
        },
        {
            id: "wallets",
            savedOrder: {!!json_encode($walletsSavedOrder ?? null)!!},
            hiddenCols: {!!json_encode($walletsHiddenCols ?? null)!!},
            tableName: "wallets",
            order: [[1, "asc"], [10, "asc"], [0, "asc"]],
            pageLength: 25,
            nonOrderableTargets: [11],
            language: {
                search: "Search wallet entries:",
                lengthMenu: "Show _MENU_ wallet entries",
                info: "Showing _START_ to _END_ of _TOTAL_ wallet entries"
            }
        }

      ];
      tableConfig.forEach(config => {
        const tableElement = $("#" + config.id);
        if (tableElement.length > 0) {
          const hiddenColumnDefs = (config.hiddenCols && config.hiddenCols.length > 0)
            ? config.hiddenCols.map(index => ({
                targets: parseInt(index),
                visible: false
              }))
            : [];

          const nonOrderableDefs = (config.nonOrderableTargets && config.nonOrderableTargets.length > 0)
            ? config.nonOrderableTargets.map(index => ({
                targets: parseInt(index),
                orderable: false,
                searchable: false
              }))
            : [];

          let dataTable = tableElement.DataTable({
            order: config.order || [[0, "desc"]],
            pageLength: config.pageLength || 10,
            colReorder: {
              order: config.savedOrder
            },
            dom: 'Bfrtip',
            buttons: ['colvis'],
            language: config.language || {},
            columnDefs: [...hiddenColumnDefs, ...nonOrderableDefs]
          });
          dataTable.on('column-reorder', function(){
            savePreferences(config.tableName, dataTable.colReorder.order(),
          getHiddenColumnIndexes(dataTable));
          });
          dataTable.on('column-visibility.dt', function(){
            savePreferences(config.tableName, dataTable.colReorder.order(),
          getHiddenColumnIndexes(dataTable));
          });
        }
      });
      function getHiddenColumnIndexes(dataTable){
        let hidden = [];
        dataTable.columns().every(function(){
          if (!this.visible()){
            hidden.push(this.index());
          }
        });
        return hidden;
      }
      function savePreferences(tableName, order, hidden){
        $.ajax({
          url: "{{ route('admin.save-column-visibility') }}",
          type: "POST",
          data: {
            _token: "{{ csrf_token() }}",
            table_key: tableName,
            column_order: order,
            hidden_columns: hidden
          },
          success: function(response){
            console.log("Preferences saved for " + tableName + ":", response);
          }
        });
      }
    });
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Dropzone CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
<style>
  /* Ensure dropzones are visible and properly styled */
  .dropzone {
    min-height: 150px !important;
    border: 2px dashed #0087F7 !important;
    border-radius: 5px;
    background: #fafafa;
    padding: 20px;
  }
  .dropzone.dz-clickable {
    cursor: pointer;
  }
  .dropzone .dz-message {
    margin: 2em 0;
    text-align: center;
    font-size: 16px;
    color: #666;
  }
</style>

<!-- Dropzone JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>

<script>
  Dropzone.autoDiscover = false;

  $(document).ready(function() {

    // Main Image Dropzone - only initialize if element exists
    if ($("#mainImageDropzone").length > 0) {
      console.log("Initializing main image dropzone...");
      try {
        let mainImageDropzone = new Dropzone("#mainImageDropzone", {
      // Use existing route defined in web.php
      url: "{{ route('product.upload.image') }}",
      maxFiles: 1,
      acceptedFiles: "image/*",
      maxFilesize: 0.5,
      addRemoveLinks: true,
      dictDefaultMessage: "Drag & drop product image here or click to upload.",
      headers: {
        'X-CSRF-TOKEN': "{{ csrf_token() }}",
      },
      success: function (file, response) {
        // Store file name to refence it during deletion
        file.uploadedFileName = response.fileName;
        document.getElementById('main_image_hidden').value = response.fileName;
      },
      removedfile: function(file) {
        // Optional: Check if the file was successfully uploaded
        if (file.uploadedFileName) {
          $.ajax({
            url: "{{ route('admin.products.delete-image') }}", // Adjust Route if needed
            type: "POST",
            data: {
              _token: "{{ csrf_token() }}",
              image: file.uploadedFileName
            },
            success: function(response) {
              console.log("Main Image deleted successfully");
              // Clear hidden field if the Image is removed
              document.getElementById('main_image_hidden').value = '';
            },
            error: function () {
              console.log("Error deleting main image");
            }
          });
        }
        // Remove preview from Dropzone UI
        var previewElement = file.previewElement;
        if (previewElement !== null) {
          previewElement.parentNode.removeChild(previewElement);
        }
      },
      error: function(file, message) {
        if (!file.alreadyRejected) {
          file.alreadyRejected = true;
          let errorContainer = document.getElementById('mainImageDropzoneError');
          if (errorContainer) {
            errorContainer.innerText = typeof message === 'string' ? message : message.message;
            errorContainer.style.display = 'block';
            setTimeout(() => {
              errorContainer.style.display = 'none';
            }, 4000);
          }
        }
        this.removeFile(file);
      },
      init: function() {
        this.on("maxfilesexceeded", function(file) {
          this.removeAllFiles();
          this.addFile(file);
        });
      }
    });
        console.log("Main image dropzone initialized successfully");
      } catch(e) {
        console.error("Error initializing main image dropzone:", e);
      }
    } else {
      console.log("Main image dropzone element not found");
    }

    // Product Images Dropzone - only initialize if element exists
    if ($("#productImagesDropzone").length > 0) {
      console.log("Initializing product images dropzone...");
      try {
        let productImagesDropzone = new Dropzone("#productImagesDropzone", {
      url: "{{ route('product.upload.images') }}",
      maxFiles: 10,
      acceptedFiles: "image/*",
      parallelUploads: 10, // Add this line to allow parallel uploads
      uploadMultiple: false, // Keep this false unless you want to send all files in one request
      maxFilesize: 0.5,
      addRemoveLinks: true,
      dictDefaultMessage: "Drag & drop product images here or click to upload.",
      headers: {
        'X-CSRF-TOKEN': "{{ csrf_token() }}",
      },
      init: function() {
        this.on("success", function(file, response) {
          // Append filename to hidden input
          let hiddenInput = document.getElementById('product_images_hidden');
          let currentVal = hiddenInput.value;
          hiddenInput.value = currentVal ? currentVal + ',' + response.fileName : response.fileName;
          file.uploadedFileName = response.fileName;
        });
        this.on("removedfile", function(file) {
          if (file.uploadedFileName) {
            let hiddenInput = document.getElementById('product_images_hidden');
            hiddenInput.value = hiddenInput.value.split(',').filter(name => name !== file.uploadedFileName).join(',');
            $.ajax({
              url: "{{ route('product.delete.temp.altimage') }}",
              type: "POST",
              data: {filename: file.uploadedFileName},
              headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
              }
            });
          }
        });
      }
    });
        console.log("Product images dropzone initialized successfully");
      } catch(e) {
        console.error("Error initializing product images dropzone:", e);
      }
    } else {
      console.log("Product images dropzone element not found");
    }

    // Product Video Dropzone - only initialize if element exists
    if ($("#productVideoDropzone").length > 0) {
      console.log("Initializing product video dropzone...");
      try {
        let productVideoDropzone = new Dropzone("#productVideoDropzone", {
      url: "{{ route('product.upload.video') }}",
      maxFiles: 1,
      acceptedFiles: "video/*",
      maxFilesize: 2,
      addRemoveLinks: true,
      dictDefaultMessage: "Drag & drop product video here or click to upload.",
      headers: {
        'X-CSRF-TOKEN': "{{ csrf_token() }}",
      },
      success: function(file, response) {
        document.getElementById('product_video_hidden').value = response.fileName;
        file.uploadedFileName = response.fileName;
      },
      removedfile: function(file) {
        if (file.uploadedFileName) {
          document.getElementById('product_video_hidden').value = '';
          $.ajax({
            url: "{{ route('product.delete.temp.video') }}",
            type: "POST",
            data: {filename: file.uploadedFileName},
            headers: {
              'X-CSRF-TOKEN': "{{ csrf_token() }}",
            }
          });
        }
        let previewElement = file.previewElement;
        if (previewElement !== null) {
          previewElement.parentNode.removeChild(previewElement);
        }
      },
      init: function() {
        this.on("maxfilesexceeded", function(file) {
          this.removeAllFiles();
          this.addFile(file);
        });
      }
    });
        console.log("Product video dropzone initialized successfully");
      } catch(e) {
        console.error("Error initializing product video dropzone:", e);
      }
    } else {
      console.log("Product video dropzone element not found");
    }

    // Product Image Sort Script - only initialize if element exists
    if ($("#sortable-images").length > 0) {
      $("#sortable-images").sortable({
      helper: "clone",
      placeholder: "sortable-placeholder",
      forcePlaceholderSize: true,
      scroll: true,
      axis: 'x', // restrict to horizontal only
      update: function(event, ui) {
        let sortedIds = [];
        $('#sortable-images .sortable-item').each(function(index) {
          sortedIds.push({
            id: $(this).data('id'),
            sort: index
          });
        });
        $.ajax({
          url: "{{ route('admin.products.update-image-sorting') }}",
          method: "POST",
          data: {
            _token: "{{ csrf_token() }}",
            sorted_images: sortedIds
          }
        });
      }
    });
    }

  }); // End of $(document).ready
</script>

<script>
    // Iniatialize submenu functionality
    $(document).ready(function(){
      $('.dropdown-submenu a.test').on("click", function(e){
        $(this).next('ul').toggle();
        e.stopPropagation();
        e.preventDefault();
      });
    });
</script>
