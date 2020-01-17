const app = getApp()

Page({
  data: {
    currNavIndex: '0',
    currModuleIndex: null,
    currModuleStatus: 'hide',
    showIndex: null
  },
  // 切换nav
  changeNav (e) {
    let navIndex = e.currentTarget.dataset.index
    this.setData({
      currNavIndex: navIndex
    })
  },
  showAll (e) {
    console.log(e)
    let status = e.currentTarget.dataset.status
    if (status === 'hide') {
      this.setData({
        currModuleIndex: e.currentTarget.dataset.index,
        currModuleStatus: 'show'
      })
    } else {
      this.setData({
        currModuleIndex: e.currentTarget.dataset.index,
        currModuleStatus: 'hide'
      })
    }
  },
  onLoad: function () {}
})