<script>
  export let text = "OR";
</script>

<div>
  <hr />
  <span>{text}</span>
</div>

<style lang="stylus">
  div
    position relative
    display inline-flex
    align-items center
    justify-content center
    width 100%

    hr
      height 1px
      border-width 0px
      width 90%
      margin 1.5rem 0
      background-color var(--overlay_back_color)

    span
      position absolute
      left 50%
      transform translateX(-50%)
      padding 0 0.8rem
      background-color var(--modal_back_color)

</style>
