(function ($) {
  /**
   * header scroll
   */
  document.addEventListener("scroll", function () {
    var header = document.querySelector(".pc-simple-header");
    var scrollPosition = window.scrollY;

    if (scrollPosition > 50) {
      header.classList.add("is-scroll-header");
    } else {
      header.classList.remove("is-scroll-header");
    }
  });

  /**
   * Line body background scroll animation
   */
  $(document).ready(function () {
    var initialBackgroundPosition =
      parseFloat(localStorage.getItem("initialBackgroundPosition")) || -100;
    var scrollSpeed = 0.8;
    var $bodyHome = $("body.home");

    // Set and update the background position on scroll
    function updateBackgroundPosition() {
      var backgroundPosition = `center ${
        initialBackgroundPosition - $(window).scrollTop() * scrollSpeed
      }px`;
      $bodyHome.css("background-position", backgroundPosition);
    }

    // Set the initial background position
    updateBackgroundPosition();

    // Update the background position on scroll
    $(window).scroll(updateBackgroundPosition);

    // Save the initial background position to local storage if not already stored
    localStorage.setItem(
      "initialBackgroundPosition",
      initialBackgroundPosition
    );
  });

  /**
   * mouse effect image hero
   */
  if ($(".is-hero-bg").length > 0) {
    document.addEventListener("DOMContentLoaded", function () {
      const image = document.querySelector(".is-hero-bg");
      const originalTransform = window
        .getComputedStyle(image)
        .getPropertyValue("transform");
      let isMobile = window.innerWidth <= 767;

      function handleMouseMove(e) {
        if (!isMobile) {
          const {clientX, clientY} = e;
          const xOffset = (clientX / window.innerWidth - 0.5) * 50;
          const yOffset = (clientY / window.innerHeight - 0.5) * 50;

          applyTransform(
            `translate(${xOffset}px, ${yOffset}px)`,
            "transform 1s ease-out"
          );
        }
      }

      function handleMouseLeave() {
        if (!isMobile) {
          applyTransform(originalTransform, "transform 1s ease-in");
        }
      }

      function handleTransitionEnd() {
        applyTransform(originalTransform, "none");
      }

      function applyTransform(transformValue, transitionValue) {
        image.style.transition = transitionValue;
        image.style.transform = transformValue;
      }

      document.addEventListener("mousemove", handleMouseMove);
      image.addEventListener("mouseleave", handleMouseLeave);
      image.addEventListener("transitionend", handleTransitionEnd);

      window.addEventListener("resize", function () {
        isMobile = window.innerWidth <= 767;
        if (isMobile) {
          applyTransform(originalTransform, "none");
        }
      });
    });
  }

  /**
   * hash anchor adjustment
   */
  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("a[href='#']").forEach(function (link) {
      link.addEventListener("click", function (event) {
        event.preventDefault();

        var target = this.getAttribute("href").substring(2);
        var targetElement = document.getElementById(target);
        var offset = window.innerWidth < 767 ? 40 : 80;

        if (targetElement) {
          window.scrollTo({
            top:
              targetElement.getBoundingClientRect().top +
              window.scrollY -
              offset,
            behavior: "smooth",
          });
        }

        // document
        //   .querySelector(".pc-simple-header-menu__mobile.pc-open")
        //   ?.classList.remove("pc-open");
        // document
        //   .querySelector("#pc-primary-overlay.pc-active")
        //   ?.classList.remove("pc-active");
      });
    });
  });

  /**
   * Close menu popup when click the menu item
   */
  function closeMenuPopup() {
    $(document).on("click", ".pc-simple-header-menu__mobile a", function (e) {
      const $menuMobile = $(".pc-simple-header-menu__mobile");
      const $menuOverlay = $("#pc-primary-overlay");
      $menuMobile.toggleClass("pc-open");
      $menuOverlay.toggleClass("pc-active");
    });
  }

  $(document).ready(function () {
    closeMenuPopup();
  });

  document.addEventListener("DOMContentLoaded", function () {
    if (typeof ep_event_booking !== "undefined") {
      ep_event_booking.enabled_guest_booking = true; // Set your desired value here
    }
  });

  /**
   * Initialize Datepicker & Timepicker
   */
  function initialize_booking_form_datepicker(
    available_dates,
    close_dates = null
  ) {
    const $datum_field = $(".pc-event-datum");
    // Destroy existing datepicker instance
    $datum_field.datepicker("destroy");

    // Datepicker
    $datum_field.datepicker({
      minDate: 0,
      beforeShowDay: function (date) {
        var dateString = $.datepicker.formatDate("yy-mm-dd", date);
        // Check if the date is in close_dates
        if (close_dates && close_dates.indexOf(dateString) !== -1) {
          return [false, "date-closed", "Closed"];
        }

        if (available_dates !== "everyday") {
          var isAvailable = available_dates.indexOf(dateString) !== -1;
          return [isAvailable, ""];
        } else {
          var day = date.getDay();
          return [day != 1];
        }
      },
      onSelect: function (dateText, inst) {
        let date = moment(dateText, "MMMM DD, YYYY", "de");
        let formattedDate = "";
        if (date.isValid()) {
          formattedDate = date.format("YYYYMMDD");
        }

        $("[name=zeit]").removeClass("pc-selected").val("");
        $("[name=datum_format]").val(formattedDate);
        ajax_check_availability();
      },
    });
  }

  /**
   * Booking form config
   */
  function booking_form_config() {
    // Form group check
    $(".pc-booking-form-fields .form-group").each(function () {
      const $this = $(this);
      const $label = $this.find("label");
      const $label_html = $label.html();

      $label.remove();

      $this
        .find(".wpcf7-form-control-wrap")
        .prepend("<label>" + $label_html + "</label>");
      $this
        .find(".wpcf7-form-control-wrap")
        .html(
          "<span class='wpcf7-form-control-wrap-inner py-2 px-3'>" +
            $this.find(".wpcf7-form-control-wrap").html() +
            "</span>"
        );
    });

    const $kurse_field = $("[name=kurse]");
    const $zeit_field = $("[name=zeit]");
    const $kapazitat_field = $("[name=kapazitat]");
    const $datum_field = $(".pc-event-datum");

    $kurse_field.prepend("<option value='' selected>Kurs wählen</option>");
    $zeit_field.prepend("<option value='' selected>Zeit wählen</option>");
    $kapazitat_field.prepend("<option value='' selected>Kapazitat wählen</option>");

    // On change select field
    $(".wpcf7-select").on("change", function () {
      const $this = $(this);
      if ($this.val() !== "") {
        $this.addClass("pc-selected");
      } else {
        $this.removeClass("pc-selected");
      }
    });

    setTimeout(() => {
      $datum_field.prop("disabled", true);
      $zeit_field.prop("disabled", true);
      $kapazitat_field.prop("disabled", true);
    }, 300);

    // Zeit on change
    $zeit_field.on("change", function () {
      const $this = $(this);
      const timeString = $this.val();
      const [startTime, endTime] = timeString.split(" - ");

      const startTimeFormatted = startTime.replace(":", "");
      const endTimeFormatted = endTime.replace(":", "");

      document.getElementsByName("event-start-time")[0].value =
        startTimeFormatted;
      document.getElementsByName("event-end-time")[0].value = endTimeFormatted;
    });
  }

  /**
   * Function on change kurse form
   */
  function select_course_form() {
    $(document).on("change", "[name=kurse]", function () {
      const $zeit_field = $("[name=zeit]");
      const $kapazitat_field = $("[name=kapazitat]");
      const $datum_field = $(".pc-event-datum");

      $zeit_field.prop("disabled", true);
      $kapazitat_field.prop("disabled", true);
      $zeit_field.removeClass("pc-selected").val("");

      if ($(this).val() === "Painting Night") {
        $datum_field.prop("disabled", true);
        $datum_field.val("");
      }

      ajax_check_availability();
    });
  }

  /**
   * Function on change time form
   */
  function select_time_form() {
    $(document).on("change", "[name=zeit]", function () {
      const $kapazitat_field = $("[name=kapazitat]");

      $kapazitat_field.prop("disabled", true);
      ajax_check_availability();
    });
  }

  /**
   * Ajax check availability
   */
  function ajax_check_availability() {
    const $zeit_field = $("[name=zeit]");
    const $kapazitat_field = $("[name=kapazitat]");
    const $datum_field = $(".pc-event-datum");

    const $booking_form = $(".pc-booking-form");
    const course = $("[name=kurse]").val();
    const datum = $("[name=datum]").val();
    const time = $zeit_field.val();

    let date = moment(datum, "MMMM DD, YYYY", "de");
    let dayName = "";
    let formattedDate = "";
    if (date.isValid()) {
      dayName = date.format("dddd");
      formattedDate = date.format("YYYYMMDD");
    }

    if (course !== "") {
      $.ajax({
        type: "POST",
        url: _palmcode.ajaxurl,
        data: {
          course: course,
          datum: datum,
          dayName: dayName,
          formattedDate: formattedDate,
          time: time,
          action: "pc_check_availability",
        },
        statusCode: {
          400: function () {
            console.log(_palmcode.strings.error_400);
          },
          403: function () {
            console.log(_palmcode.strings.error_403);
          },
          500: function () {
            console.log(_palmcode.strings.error_500);
          },
        },
        beforeSend: function () {
          $(".pc-availability").html("");
          $booking_form.find(".pc-kapazitat").addClass("pc-processing");
          $booking_form.find(".pc-event-datum").addClass("pc-processing");
          $('.wpcf7-form-control-wrap[data-name="kapazitat"]')
            .find(".wpcf7-not-valid-tip")
            .remove();
          if (time === "") {
            $booking_form.find(".pc-zeit").addClass("pc-processing");
          }
        },
        success: function (response) {
          // console.log(response);
          if (response.success) {
            if (time === "") {
              $("[name=zeit]").html(response.time_options);
            }

            $(".pc-availability").html(response.max_capacity);
            $("[name=kapazitat]").html(response.max_capacity_options);

            if (
              response.date_field === "empty" ||
              response.max_capacity_number === 0
            ) {
              $datum_field.prop("disabled", false);
              $datum_field.prop("readonly", true);

              if (response.max_capacity_number === 0) {
                $('.wpcf7-form-control-wrap[data-name="kapazitat"]').append(
                  '<span class="wpcf7-not-valid-tip" aria-hidden="true">Alles ausgebucht. Anderes Datum wählen.</span>'
                );
              }
            } else {
              $zeit_field.prop("disabled", false);
              if (response.time_field !== "empty") {
                $kapazitat_field.prop("disabled", false);
              }
            }

            initialize_booking_form_datepicker(
              response.dates,
              response.close_dates
            );
          }

          $booking_form.find(".pc-zeit").removeClass("pc-processing");
          $booking_form.find(".pc-kapazitat").removeClass("pc-processing");
          $booking_form.find(".pc-event-datum").removeClass("pc-processing");
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", error);
        },
      });
    }
  }

  /**
   * Auto selected event in form when on single event page
   */
  function auto_selected_event() {
    if ($("body").hasClass("single-em_event")) {
      const title = $("#event-heading").text();
      setTimeout(() => {
        // console.log(title);
        const $kurse_field = $("[name=kurse]");
        $kurse_field.addClass("pc-selected");
        $kurse_field.val(title);
        ajax_check_availability();
      }, 300);
    }
  }

  /**
   * When email sent successfully
   */
  function cf7_email_sent_callback() {
    document.addEventListener(
      "wpcf7mailsent",
      function (event) {
        const form_id = event.detail.contactFormId;

        if (form_id === 320) {
          const $form_fields = $(".pc-booking-form-fields");
          $form_fields.find(".pc-availability").html("");
          const $thankyou_box = $(".pc-thankyou__box");

          $form_fields.toggleClass("d-none");
          $thankyou_box.toggleClass("d-none");

          const form_data = event.detail.inputs;
          const vorname = form_data[0].value;
          const nachname = form_data[1].value;
          const telefonnummer = form_data[2].value;
          const email = form_data[3].value;
          const kurse = form_data[4].value;
          const datum = form_data[5].value;
          const zeit = form_data[6].value;
          const kapazitat = form_data[7].value;

          $thankyou_box
            .find(".pc-reservation-name")
            .text(vorname + " " + nachname);
          $thankyou_box.find(".pc-reservation-phone").text(telefonnummer);
          $thankyou_box.find(".pc-reservation-email").text(email);
          $thankyou_box.find(".pc-reservation-date").text(datum);
          $thankyou_box.find(".pc-reservation-person").text(kapazitat);
          $thankyou_box.find(".pc-reservation-time").text(zeit);
          $thankyou_box.find(".pc-reservation-course").text(kurse);
        }
      },
      false
    );
  }

  /**
   * Thank you button clicked
   */
  function thankyou_button_config() {
    // Other reservation button clicked
    $(document).on("click", ".pc-thankyou__button", function (e) {
      e.preventDefault();

      const $form_fields = $(".pc-booking-form-fields");
      const $thankyou_box = $(".pc-thankyou__box");
      const $kurse_field = $("[name=kurse]");
      const $zeit_field = $("[name=zeit]");
      const $kapazitat_field = $("[name=kapazitat]");
      const $datum_field = $("[name=datum]");

      $form_fields.toggleClass("d-none");
      $thankyou_box.toggleClass("d-none");
      $zeit_field.prop("disabled", true).removeClass("pc-selected");
      $kapazitat_field.prop("disabled", true).removeClass("pc-selected");
      $datum_field.val("").prop("readonly", false).prop("disabled", true);
      $kurse_field.removeClass("pc-selected");
      auto_selected_event();
      ajax_check_availability();
    });
  }

  /**
   * Anchor position Datenschutz
   */
  function anchor_position_datenschutz() {
    document.querySelectorAll(".index-link").forEach((link) => {
      link.addEventListener("click", function (event) {
        event.preventDefault(); // Mencegah perilaku default
        const targetId = this.getAttribute("href"); // Mendapatkan ID target dari atribut href
        const targetElement = document.querySelector(targetId); // Mendapatkan elemen target

        if (targetElement) {
          const topOffset = 32; // Jarak tambahan dari atas
          const elementPosition = targetElement.offsetTop; // Posisi elemen target dari atas halaman
          const offsetPosition = elementPosition - topOffset;

          window.scrollTo({
            top: offsetPosition,
            behavior: "smooth", // Scroll secara smooth
          });
        }
      });
    });
  }

  $(document).ready(function () {
    booking_form_config();
    select_course_form();
    select_time_form();
    auto_selected_event();
    cf7_email_sent_callback();
    thankyou_button_config();
    anchor_position_datenschutz();
  });
})(jQuery);
