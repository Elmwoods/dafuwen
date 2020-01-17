const app = getApp()

Page({
  data: {
    currNavIndex: '0'
  },
  // 切换nav
  changeNav (e) {
    let navIndex = e.currentTarget.dataset.index
    this.setData({
      currNavIndex: navIndex
    })
  },
  onLoad: function () {}
})