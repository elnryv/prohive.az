export function createStepper(totalSteps) {
  const el = document.createElement('div');
  el.className = 'stepper';
  for (let i = 0; i < totalSteps; i++) {
    const dot = document.createElement('div');
    dot.className = 'stepper-dot';
    el.appendChild(dot);
  }

  function setStep(index) {
    [...el.children].forEach((dot, i) => {
      dot.className = 'stepper-dot' + (i < index ? ' done' : i === index ? ' current' : '');
    });
  }

  setStep(0);
  return { el, setStep };
}
