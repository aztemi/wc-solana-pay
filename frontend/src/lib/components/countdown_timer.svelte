<script>
  import { onMount, onDestroy } from "svelte";
  import { order } from "../store/order";

  const ONE_MINUTE = 60 * 1000; // in ms
  const EXPIRE_DURATION = 5; // in minutes
  const ANIMATION_MS = EXPIRE_DURATION * ONE_MINUTE;

  const w = 960; // svg viewbox width
  const cx = Math.round(w / 2); // center position
  const fs = Math.round((w * 10) / 24); // font-size
  const sw = Math.round(cx / 16); // stroke-width
  const r = Math.round(w / 3); // circle radius
  const c = Math.round(Math.PI * r * 2); // circle circumference

  let timer = null;
  let timeLeft = EXPIRE_DURATION;
  let countdownStarted = false;

  function countdown() {
    if (countdownStarted) timeLeft--;
    else countdownStarted = true; // just another way to wait 1 loop before starting countdown

    if (timeLeft <= 0) order.timeout();
  }

  onMount(() => {
    timer = setInterval(countdown, ONE_MINUTE);
  });

  onDestroy(() => {
    if (timer) clearInterval(timer);
    timer = null;
  });
</script>

{#if countdownStarted}
  <i style="--font-size:{fs}px; --stroke-width:{sw}px; --length:{c}px; --animate-time:{ANIMATION_MS}ms">
    <svg viewBox="0 0 {w} {w}">
      <title>Time left: {timeLeft} minute(s)</title>
      <circle {r} {cx} cy={cx} />
      <text x={cx} y={cx}>{timeLeft}</text>
    </svg>
  </i>
{/if}

<style lang="stylus">
  i
    border 0
    height 2rem
    width 2rem
    padding 0
    margin-left 0.5rem
    display flex
    justify-content center
    align-items center

    text
      fill var(--modal_border_color)
      font-size var(--font-size)
      font-style normal
      text-anchor middle
      dominant-baseline central
      alignment-baseline central

    circle
      fill none
      stroke var(--modal_border_color)
      stroke-width var(--stroke-width)
      stroke-linecap round
      stroke-dashoffset 0px
      stroke-dasharray var(--length)
      transform-origin center
      transform rotateY(-180deg) rotateZ(-90deg)
      animation-name countdown
      animation-duration var(--animate-time)
      animation-timing-function linear
      animation-iteration-count infinite
      animation-fill-mode forwards

  @keyframes countdown
    from
      stroke-dashoffset 0px
    to
      stroke-dashoffset var(--length)

</style>
