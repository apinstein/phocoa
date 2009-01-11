DDSend = function(id, sGroup, config) {

    if (id) {
        // bind this drag drop object to the
        // drag source object
        this.init(id, sGroup, config);
        this.initFrame();
    }

    var s = this.getDragEl().style;
    s.borderColor = "transparent";
    s.backgroundColor = "#f6f5e5";
    s.opacity = 0.76;
    s.filter = "alpha(opacity=76)";
};

// extend proxy so we don't move the whole object around
DDSend.prototype = new YAHOO.util.DDProxy();

DDSend.prototype.onDragDrop = function(e, id) {
    // this is called when the source object dropped
    // on a valid target
    document.fire('yuitreeview:drop', { itemId: this.id, droppedOnId: id });
}

DDSend.prototype.startDrag = function(x, y) {
    // called when source object first selected for dragging
    // draw a red border around the drag object we create
    var dragEl = this.getDragEl();
    var clickEl = this.getEl();

    dragEl.innerHTML = clickEl.innerHTML;
    dragEl.className = clickEl.className;
    dragEl.style.color = clickEl.style.color;
    dragEl.style.border = "1px solid red";

};

DDSend.prototype.onDragEnter = function(e, id) {
    var el;

    // this is called anytime we drag over
    // a potential valid target
    // highlight the target in red
    if ("string" == typeof id) {
        el = YAHOO.util.DDM.getElement(id);
    } else {
        el = YAHOO.util.DDM.getBestMatch(id).getEl();
    }

    el.style.border = "1px solid red";
};

DDSend.prototype.onDragOut = function(e, id) {
    var el;

    // this is called anytime we drag out of
    // a potential valid target
    // remove the highlight
    if ("string" == typeof id) {
        el = YAHOO.util.DDM.getElement(id);
    } else {
        el = YAHOO.util.DDM.getBestMatch(id).getEl();
    }

    el.style.border = "";
}

DDSend.prototype.endDrag = function(e) {
   // override so source object doesn't move when we are done
}

