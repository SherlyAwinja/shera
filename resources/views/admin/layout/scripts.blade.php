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
<!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
<script src="{{ asset('admin/js/adminlte.js') }}"></script>
<!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
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
    // NOTICE!! DO NOT USE ANY OF THIS JAVASCRIPT
    // IT'S ALL JUST JUNK FOR DEMO
    // ++++++++++++++++++++++++++++++++++++++++++
    
    /* apexcharts
     * -------
     * Here we will create a few charts using apexcharts
     */
    
    //-----------------------
    // - MONTHLY SALES CHART -
    //-----------------------
    
    const sales_chart_options = {
      series: [
        {
          name: 'Digital Goods',
          data: [28, 48, 40, 19, 86, 27, 90],
        },
        {
          name: 'Electronics',
          data: [65, 59, 80, 81, 56, 55, 40],
        },
      ],
      chart: {
        height: 180,
        type: 'area',
        toolbar: {
          show: false,
        },
      },
      legend: {
        show: false,
      },
      colors: ['#0d6efd', '#20c997'],
      dataLabels: {
        enabled: false,
      },
      stroke: {
        curve: 'smooth',
      },
      xaxis: {
        type: 'datetime',
        categories: [
          '2023-01-01',
          '2023-02-01',
          '2023-03-01',
          '2023-04-01',
          '2023-05-01',
          '2023-06-01',
          '2023-07-01',
        ],
      },
      tooltip: {
        x: {
          format: 'MMMM yyyy',
        },
      },
    };
    
    const sales_chart = new ApexCharts(
      document.querySelector('#sales-chart'),
      sales_chart_options,
    );
    sales_chart.render();
    
    //---------------------------
    // - END MONTHLY SALES CHART -
    //---------------------------
    
    function createSparklineChart(selector, data) {
      const options = {
        series: [{ data }],
        chart: {
          type: 'line',
          width: 150,
          height: 30,
          sparkline: {
            enabled: true,
          },
        },
        colors: ['var(--bs-primary)'],
        stroke: {
          width: 2,
        },
        tooltip: {
          fixed: {
            enabled: false,
          },
          x: {
            show: false,
          },
          y: {
            title: {
              formatter() {
                return '';
              },
            },
          },
          marker: {
            show: false,
          },
        },
      };
    
      const chart = new ApexCharts(document.querySelector(selector), options);
      chart.render();
    }
    
    const table_sparkline_1_data = [25, 66, 41, 89, 63, 25, 44, 12, 36, 9, 54];
    const table_sparkline_2_data = [12, 56, 21, 39, 73, 45, 64, 52, 36, 59, 44];
    const table_sparkline_3_data = [15, 46, 21, 59, 33, 15, 34, 42, 56, 19, 64];
    const table_sparkline_4_data = [30, 56, 31, 69, 43, 35, 24, 32, 46, 29, 64];
    const table_sparkline_5_data = [20, 76, 51, 79, 53, 35, 54, 22, 36, 49, 64];
    const table_sparkline_6_data = [5, 36, 11, 69, 23, 15, 14, 42, 26, 19, 44];
    const table_sparkline_7_data = [12, 56, 21, 39, 73, 45, 64, 52, 36, 59, 74];
    
    createSparklineChart('#table-sparkline-1', table_sparkline_1_data);
    createSparklineChart('#table-sparkline-2', table_sparkline_2_data);
    createSparklineChart('#table-sparkline-3', table_sparkline_3_data);
    createSparklineChart('#table-sparkline-4', table_sparkline_4_data);
    createSparklineChart('#table-sparkline-5', table_sparkline_5_data);
    createSparklineChart('#table-sparkline-6', table_sparkline_6_data);
    createSparklineChart('#table-sparkline-7', table_sparkline_7_data);
    
    //-------------
    // - PIE CHART -
    //-------------
    
    const pie_chart_options = {
      series: [700, 500, 400, 600, 300, 100],
      chart: {
        type: 'donut',
      },
      labels: ['Chrome', 'Edge', 'FireFox', 'Safari', 'Opera', 'IE'],
      dataLabels: {
        enabled: false,
      },
      colors: ['#0d6efd', '#20c997', '#ffc107', '#d63384', '#6f42c1', '#adb5bd'],
    };
    
    const pie_chart = new ApexCharts(document.querySelector('#pie-chart'), pie_chart_options);
    pie_chart.render();
    
    //-----------------
    // - END PIE CHART -
    //-----------------
</script>
<!-- jQuery -->
<script src="{{ url('admin/js/jquery-3.7.1.min.js') }}"></script>

<!-- jQuery UI -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- Custom Script -->
<script src="{{ url('admin/js/custom.js') }}"></script>

<!-- DataTables -->
<link rel="stylesheet" 
href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#categories').DataTable();
        $('#subadmins').DataTable();
        $('#products').DataTable();
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