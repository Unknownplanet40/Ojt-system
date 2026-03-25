let Toast;
const toastQueue = [];
const modalQueue = [];
const maxVisibleToasts = 3;
let activeToasts = 0;
const maxActiveModals = 1;
let activeModals = 0;

function processToastQueue() {
  if (activeToasts >= maxVisibleToasts) return;
  if (toastQueue.length === 0) return;

  const data = toastQueue.shift();
  activeToasts++;

  Toast.fire({
    ...data,
    didClose: () => {
      activeToasts--;
      processToastQueue();
    },
  });
}

// Prevent adding duplicate toast to queue
function isDuplicateToast(newToast) {
  return toastQueue.some((t) => t.icon === newToast.icon && t.title === newToast.title && t.timer === newToast.timer && t.position === newToast.position && t.theme === newToast.theme);
}

export function ToastVersion(theme = "bootstrap-5-light", title = "This is a toast notification!", icon = "info", timer = 3000, position = "top-end", topOffset = "none") {
  const validThemes = ["bootstrap-5-light", "bootstrap-5-dark"];
  const validIcons = ["info", "success", "error", "warning", "question"];
  const validOffsets = ["none", "0", "1", "2", "3", "4", "5", "8"];
  const validPositions = ["top", "top-start", "top-end", "center", "center-start", "center-end", "bottom", "bottom-start", "bottom-end"];

  const themeToApply = validThemes.includes(theme.toLowerCase()) ? theme.toLowerCase() : "bootstrap-5-light";
  const iconToApply = validIcons.includes(icon.toLowerCase()) ? icon.toLowerCase() : "info";
  const positionToApply = validPositions.includes(position.toLowerCase()) ? position.toLowerCase() : "top-end";
  const topOffsetToApply = validOffsets.includes(topOffset.toLowerCase()) ? topOffset.toLowerCase() : "none";

  // map position → animation
  function getAnimationClass(pos) {
    switch (pos) {
      case "top":
        return "bounce-in-top";
      case "top-start":
      case "center-start":
      case "bottom-start":
        return "bounce-in-left";
      case "top-end":
      case "center-end":
      case "bottom-end":
        return "bounce-in-right";
      case "center":
        return "bounce-in-fwd";
      case "bottom":
        return "bounce-in-bottom";
      default:
        return "bounce-in-right";
    }
  }

  const animationClass = getAnimationClass(positionToApply);

  const Toast = Swal.mixin({
    toast: true,
    position: positionToApply,
    showConfirmButton: false,
    timerProgressBar: true,

    // disable default animation so yours takes over
    showClass: { popup: "" },
    hideClass: { popup: "swal2-hide" },

    customClass: {
      popup:
        "bg-blur-5 bg-semi-transparent border-1 rounded-3 fade-bg " +
        (topOffsetToApply !== "none" ? `mt-${topOffsetToApply}` : ""),
      timerProgressBar: "rounded-3 bg-gradient",
    },

    // 🔑 THIS is where animation should be applied
    didOpen: (toastEl) => {
      // reset animation if reused
      toastEl.classList.remove(
        "bounce-in-top",
        "bounce-in-left",
        "bounce-in-right",
        "bounce-in-bottom",
        "bounce-in-fwd"
      );

      // force reflow so animation retriggers
      void toastEl.offsetWidth;

      toastEl.classList.add(animationClass);

      console.log(`Applied animation: ${animationClass}`);
    },
  });

  const newToast = {
    icon: iconToApply,
    title: title,
    timer: timer === 0 ? false : timer,
    position: positionToApply,
    theme: themeToApply,
  };

  if (!isDuplicateToast(newToast)) {
    toastQueue.push(newToast);
    processToastQueue();
  }
}

function processModalQueue() {
  if (activeModals >= maxActiveModals) return;
  if (modalQueue.length === 0) return;

  const data = modalQueue.shift();
  activeModals++;

  Swal.fire({
    ...data,
    didClose: () => {
      activeModals--;
      processModalQueue();
    },
  });
}

// export function ToastVersion(
//   theme = "bootstrap-5-light",
//   title = "This is a toast notification!",
//   icon = "info",
//   timer = 3000,
//   position = "top-end",
//   topOffset = "none"
// ) {
//   const validThemes = ["bootstrap-5-light","bootstrap-5-dark"];
//   const validIcons = ["info","success","error","warning","question"];
//   const validOffsets = ["none","0","1","2","3","4","5"];
//   const validPositions = [
//     "top","top-start","top-end",
//     "center","center-start","center-end",
//     "bottom","bottom-start","bottom-end"
//   ];

//   const themeToApply = validThemes.includes(theme.toLowerCase()) ? theme.toLowerCase() : "bootstrap-5-light";
//   const iconToApply = validIcons.includes(icon.toLowerCase()) ? icon.toLowerCase() : "info";
//   const positionToApply = validPositions.includes(position.toLowerCase()) ? position.toLowerCase() : "top-end";
//   const topOffsetToApply = validOffsets.includes(topOffset.toLowerCase()) ? topOffset.toLowerCase() : "none";

//   Toast = Swal.mixin({
//     toast: true,
//     position: positionToApply,
//     showConfirmButton: false,
//     timerProgressBar: true,
//     showClass: { popup: "" },
//     hideClass: { popup: "swal2-hide" },
//     customClass: {
//       popup: "bg-blur-5 bg-semi-transparent border-1 rounded-3 " + (topOffsetToApply !== "none" ? `mt-${topOffsetToApply}` : ""),
//       timerProgressBar: "rounded-3 bg-gradient"
//     }
//   });

//   toastQueue.push({
//     icon: iconToApply,
//     title: title,
//     timer: timer === 0 ? false : timer,
//     position: positionToApply,
//     theme: themeToApply
//   });

//   processToastQueue();
// }

export function ModalVersion(theme = "bootstrap-5-light", title = "This is a modal!", text = "Here is some more information about this modal.", icon = "info", timer = 0, position = "center") {
  const validThemes = ["bootstrap-5-light", "bootstrap-5-dark"];
  const validIcons = ["info", "success", "error", "warning", "question"];
  const validPositions = ["top", "top-start", "top-end", "center", "center-start", "center-end", "bottom", "bottom-start", "bottom-end"];
  const iconToApply = validIcons.includes(icon.toLowerCase()) ? icon.toLowerCase() : "info";
  const themeToApply = validThemes.includes(theme.toLowerCase()) ? theme.toLowerCase() : "bootstrap-5-light";
  const positionToApply = validPositions.includes(position.toLowerCase()) ? position.toLowerCase() : "center";

  modalQueue.push({
    theme: themeToApply,
    title: title,
    text: text,
    icon: iconToApply,
    showConfirmButton: false,
    timer: timer === 0 ? false : timer,
    position: positionToApply,
    allowOutsideClick: timer === 0 ? true : false,
    customClass: {
      popup: "bg-blur-5 bg-semi-transparent border-1 rounded-2",
    },
  });

  processModalQueue();
}
