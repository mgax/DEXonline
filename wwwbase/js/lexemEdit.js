var struct_anyChanges = false;
var COOKIE_NAME = 'lexemEdit';
$.cookie.json = true;

function lexemEditInit() {
  $('#meaningTree').tree({
    animate: true,
    dnd: true,
    onBeforeSelect: meaningEditorUnchanged,
    onSelect: beginMeaningEdit,
  });
  $('#addMeaningButton').click(addMeaning);
  $('#addSubmeaningButton').click(addSubmeaning);
  $('#deleteMeaningButton').click(deleteMeaning);

  $('#editorSources').select2({
    matcher: sourceMatcher,
    placeholder: 'adaugă o sursă...',
    width: '315px',
  });
  $('#editorSources').select2('enable', false);

  $('#editorTags').select2({
    placeholder: 'adaugă o etichetă...',
    width: '315px',
  });
  $('#editorTags').select2('enable', false);

  $('#editorSynonyms').select2({
    ajax: struct_lexemAjax,
    initSelection: select2InitSelection,
    minimumInputLength: 1,
    multiple: true,
    placeholder: 'adaugă un sinonim...',
    width: '315px',
  });
  $('#editorSynonyms').select2('enable', false);

  $('#editorAntonyms').select2({
    ajax: struct_lexemAjax,
    initSelection: select2InitSelection,
    minimumInputLength: 1,
    multiple: true,
    placeholder: 'adaugă un antonim...',
    width: '315px',
  });
  $('#editorAntonyms').select2('enable', false);

  $('#editorInternalRep, #editorInternalComment, #editorSources, #editorTags, #editorSynonyms, #editorAntonyms').bind(
    'change keyup input paste', function() { struct_anyChanges = true; });

  $('#variantIds').select2({
    ajax: struct_lexemAjax,
    initSelection: select2InitSelectionAjax,
    minimumInputLength: 1,
    multiple: true,
    width: '173px',
  });

  $('#variantOfId').select2({
    ajax: struct_lexemAjax,
    allowClear: true,
    initSelection: select2InitSelectionAjaxSingle,
    minimumInputLength: 1,
    placeholder: '(opțional)',
    width: '173px',
  });

  $('#associateDefinitionId').select2({
    ajax: struct_definitionAjax,
    formatResult: function(item) {
      return item.text + ' (' + item.source + ') [' + item.id + ']';
    },
    minimumInputLength: 1,
    placeholder: 'asociază o definiție',
    width: '400px',
  });

  $('#editMeaningAcceptButton').click(acceptMeaningEdit);
  $('#editMeaningCancelButton').click(endMeaningEdit);
  $('.lexemEditSaveButton').click(saveEverything);
  $('.toggleRepLink').click(toggleRepClick);
  $('.toggleStructuredLink').click(toggleStructuredClick);
  $('.defFilterLink').click(defFilterClick);

  $('#lexemSourceIds').select2({
    matcher: sourceMatcher,
    placeholder: 'surse care atestă flexiunea',
    width: '173px',
  });
  // Disable the select2 when the HTML select is disabled. This doesn't happen by itself.
  $('#lexemSourceIds').select2('readonly', $('#lexemSourceIds').is('[readonly]'));
  $('#similarLexemId').select2({
    ajax: struct_lexemAjax,
    minimumInputLength: 1,
    placeholder: 'sau indicați un lexem similar',
    width: '300px',
  }).on('change', similarLexemChange);

  $('.mergeLexem').click(mergeLexemButtonClick);
  $('.similarLink').click(similarLinkClick);

  wmInit();
}

function sourceMatcher(term, text) {
  term = term.replace('ş', 'ș').replace('Ş', 'Ș').replace('ţ', 't').replace('Ţ', 'Ț');
  return text.toUpperCase().indexOf(term.toUpperCase()) != -1;
}

function addMeaning() {
  if (!meaningEditorUnchanged()) {
    return false;
  }
  var node = $('#meaningTree').tree('getSelected');
  var parent;
  if (node) {
    var parentNode = $('#meaningTree').tree('getParent', node.target);
    parent = parentNode ? parentNode.target : null;
  } else {
    parent = null;
  }
  appendAndSelectNode(parent);
}

function addSubmeaning() {
  if (!meaningEditorUnchanged()) {
    return false;
  }
  var node = $('#meaningTree').tree('getSelected');
  if (node) {
    appendAndSelectNode(node.target);
  }
}

function appendAndSelectNode(target) {
  var randomId = Math.floor(Math.random() * 1000000000) + 1;
  $('#meaningTree').tree('append', {
    parent: target,
    data: [{ 'id' : randomId, 'text': $('#stemNode').html() }]
  });

  // Now find and select it
  var node = $('#meaningTree').tree('find', randomId);
  $('#meaningTree').tree('select', node.target);
}

function deleteMeaning() {
  if (!meaningEditorUnchanged()) {
    return false;
  }
  var node = $('#meaningTree').tree('getSelected');
  if (node) {
    var numChildren = $('#meaningTree').tree('getChildren', node.target).length;
    if (!numChildren || confirm('Confirmați ștergerea sensului și a tuturor subsensurilor?')) {
      $('#meaningTree').tree('remove', node.target);
    }
  }
}

function meaningEditorUnchanged(node) {
  return !struct_anyChanges || confirm('Aveți deja un sens în curs de modificare. Confirmați renunțarea la modificări?');
}

function beginMeaningEdit() {
  struct_anyChanges = false;
  var domNode = $('#meaningTree').tree('getSelected').target;
  var node = $(domNode);
  $('#editorInternalRep, #editorInternalComment, #editMeaningAcceptButton, #editMeaningCancelButton').removeAttr('disabled');
  $('#editorInternalRep').val(node.find('span.internalRep').text());
  $('#editorInternalComment').val(node.find('span.internalComment').text());
  $('#editorSources').select2('val', node.find('span.sourceIds').text().split(','));
  $('#editorSources').select2('enable');
  $('#editorTags').select2('val', node.find('span.meaningTagIds').text().split(','));
  $('#editorTags').select2('enable');

  var synonymIds = node.find('span.synonymIds').text().split(',');
  var synonyms = node.find('.synonyms .tag');
  $('#editorSynonyms').select2('data', synonyms.map(function(index) {
    return { id: synonymIds[index], text: $(this).text() };
  }));
  $('#editorSynonyms').select2('enable');

  var antonymIds = node.find('span.antonymIds').text().split(',');
  var antonyms = node.find('.antonyms .tag');
  $('#editorAntonyms').select2('data', antonyms.map(function(index) {
    return { id: antonymIds[index], text: $(this).text() };
  }));
  $('#editorAntonyms').select2('enable');
}

function acceptMeaningEdit() {
  struct_anyChanges = false;
  var domNode = $('#meaningTree').tree('getSelected').target;
  var node = $(domNode);

  // Update internal and HTML definition
  var internalRep = $('#editorInternalRep').val();
  node.find('span.internalRep').text(internalRep);
  $.post(wwwRoot + 'ajax/htmlize.php',
         { internalRep: internalRep, sourceId: 0 },
         function(data) { node.find('span.htmlRep').html(data); },
        'text');

  // Update internal and HTML comment
  var internalComment = $('#editorInternalComment').val();
  node.find('span.internalComment').text(internalComment);
  $.post(wwwRoot + 'ajax/htmlize.php',
         { internalRep: internalComment, sourceId: 0 },
         function(data) { node.find('span.htmlComment').html(data); },
        'text');

  // Update sources and sourceIds
  var sourceIds = $('#editorSources').val();
  node.find('span.sourceIds').text(sourceIds ? sourceIds.join(',') : '');
  node.find('span.sources').text('');
  $('#editorSources option:selected').each(function() {
    node.find('span.sources').append('<span class="tag">' + $(this).text() + '</span>');
  });

  // Update meaning tags and meaningIds
  var meaningTagIds = $('#editorTags').val();
  node.find('span.meaningTagIds').text(meaningTagIds ? meaningTagIds.join(',') : '');
  node.find('span.meaningTags').text('');
  $('#editorTags option:selected').each(function() {
    node.find('span.meaningTags').append('<span class="tag">' + $(this).text() + '</span>');
  });

  // Update synonym tags and synonymIds
  var synonymData = $('#editorSynonyms').select2('data');
  node.find('span.synonymIds').text($.map(synonymData, function(rec, i) { return rec.id; }));
  node.find('span.synonyms').text('');
  $.map(synonymData, function(rec, i) {
    node.find('span.synonyms').append('<span class="tag">' + rec.text + '</span>');
  });

  // Update antonym tags and antonymIds
  var antonymData = $('#editorAntonyms').select2('data');
  node.find('span.antonymIds').text($.map(antonymData, function(rec, i) { return rec.id; }));
  node.find('span.antonyms').text('');
  $.map(antonymData, function(rec, i) {
    node.find('span.antonyms').append('<span class="tag">' + rec.text + '</span>');
  });

  // Now update the tree node
  $('#meaningTree').tree('update', { target: domNode, text: node.find('.tree-title').html() });
}

function endMeaningEdit() {
  struct_anyChanges = false;
  $('#editorInternalRep, #editorInternalComment, #editMeaningAcceptButton, #editMeaningCancelButton').attr('disabled', 'disabled');
  $('#editorInternalRep').val('');
  $('#editorInternalComment').val('');
  $('#editorSources').select2('val', []);
  $('#editorSources').select2('enable', false);
  $('#editorTags').select2('val', []);
  $('#editorTags').select2('enable', false);
  $('#editorSynonyms').select2('data', []);
  $('#editorSynonyms').select2('enable', false);
  $('#editorAntonyms').select2('data', []);
  $('#editorAntonyms').select2('enable', false);
}

// Iterate a meaning tree node recursively and collect meaning-related fields
function meaningTreeWalk(node, results, level) {
  var jqNode = $(node.target);
  results.push({ 'id': jqNode.find('span.id').text(),
                 'level' : level,
                 'internalRep': jqNode.find('span.internalRep').text(),
                 'internalComment': jqNode.find('span.internalComment').text(),
                 'sourceIds': jqNode.find('span.sourceIds').text(),
                 'meaningTagIds': jqNode.find('span.meaningTagIds').text(),
                 'synonymIds': jqNode.find('span.synonymIds').text(),
                 'antonymIds': jqNode.find('span.antonymIds').text(),
               });
  var children = $('#meaningTree').tree('getChildren', node.target);
  for (var i = 0; i < children.length; i++) {
    meaningTreeWalk(children[i], results, level + 1);
  }
}

function saveEverything() {
  if (struct_anyChanges) {
    acceptMeaningEdit();
  }
  var results = new Array();
  var roots = $('#meaningTree').tree('getRoots');
  for (var i = 0; i < roots.length; i++) {
    meaningTreeWalk(roots[i], results, 0);
  }
  $('input[name=jsonMeanings]').val(JSON.stringify(results));
  $('#meaningForm').submit();
}

/* Definitions can be shown as internal or HTML notation, with abbreviations expanded or collapsed. This gives rise to four combinations, coded on
 * two bits each. Clicking on the "show / hide HTML" and show / hide abbreviations" fiddles some bits and sets the "visible" class on the
 * appropriate div. */
function toggleRepClick() {
  // Hide the old definition
  var oldActive = $(this).closest('.defDetails').prevAll('[data-active]');
  oldActive.slideToggle().removeAttr('data-active');

  // Recalculate the code and show the new definition
  var code = oldActive.attr('data-code') ^ $(this).attr('data-order');
  var newActive = $(this).closest('.defDetails').prevAll('[data-code=' + code + ']');
  newActive.slideToggle().attr('data-active', '');

  // Toggle the link text
  var tmp = $(this).text();
  $(this).text($(this).attr('data-other-text'));
  $(this).attr('data-other-text', tmp);
  return false;
}

function toggleStructuredClick() {
  var parent = $(this).closest('.defWrapper');
  var id = parent.attr('id').split('_')[1];
  if (parent.hasClass('structured')) {
    $(this).text('nestructurată');
    parent.removeClass('structured');
    parent.addClass('unstructured');
    var value = 0;
  } else {
    $(this).text('structurată');
    parent.removeClass('unstructured');
    parent.addClass('structured');
    var value = 1;
  }
  $.get(wwwRoot + 'ajax/setDefinitionStructured.php?id=' + id + '&value=' + value);
  return false;
}

function defFilterClick() {
  if ($(this).hasClass('structured')) {
    $('.defWrapper.unstructured').hide('slow');
  } else {
    $('.defWrapper.unstructured').show('slow');
  }
  if ($(this).hasClass('unstructured')) {
    $('.defWrapper.structured').hide('slow');
  } else {
    $('.defWrapper.structured').show('slow');
  }
  return false;
}

function mergeLexemButtonClick() {
  var id = $(this).attr('id').split('_')[1];
  $('input[name=mergeLexemId]').val(id);
}

/* Set the model type, model number and restriction values */
function similarLinkClick() {
  var parts = $(this).attr('id').split('_');
  updateParadigm(parts[1], parts[2], parts[3]);
  return false;
}

function similarLexemChange(e) {
  var url = wwwRoot + 'ajax/getModelByLexemId.php?id=' + e.val;
  $.get(url, null, null, 'json')
    .done(function(data) {
      updateParadigm(data.modelType, data.modelNumber, data.restriction);
    });
}

function updateParadigm(modelType, modelNumber, restriction) {
  $('#modelTypeListId').val(modelType); // Does not trigger the onchange event
  updateModelList(false, modelNumber);
  $('input[name=restr\\[\\]]').each(function() {
    $(this).prop('checked', restriction.indexOf($(this).val()) != -1);
  });
}

// Initializes the window manager
function wmInit() {
  $('.box').each(function() {
    // Convert each box to a window
    var $w = $().WM('open');
    $w.find('.titlebartext').text($(this).attr('data-title'));
    $w.attr('data-id', $(this).attr('data-id'));
    $w.find('.windowcontent').append($(this).children());
    $('#wmCanvas').append($w);
  });
  wmSetCoordinates();

  // Set some handlers for moving, resizing, and resetting the interface
  $('#resizerproxy').mouseup(wmSetCookie);
  $('#moverproxy').mouseup(wmSetCookie);
  $('#interfaceResetLink').click(wmInterfaceReset);
}

// Sets the coordinates for each window based on the cookie (if available) or on HTML5 attributes of the original box
function wmSetCoordinates() {
  var props = ['left', 'top', 'width', 'height'];
  var cookie = $.cookie(COOKIE_NAME);

  $('.window').each(function() {
    var id = $(this).attr('data-id');
    if (cookie) {
      for (var i = 0; i < props.length; i++) {
        $(this).css(props[i], cookie[id][props[i]] + 'px');
      }
      if (cookie[id].minimized) {
        $(this).WM('minimize');
      }
    } else {
      // No cookie - load the corresponding box
      var box = $('.box[data-id="' + id + '"]'); 
      for (var i = 0; i < props.length; i++) {
        if (typeof(box.attr('data-' + props[i])) != 'undefined') {
          var value = parseInt(box.attr('data-' + props[i]));
          if (props[i] == 'left') {
            value += parseInt($('#wmCanvas').offset().left);
          } else if (props[i] == 'top') {
            value += parseInt($('#wmCanvas').offset().top);
          }
          $(this).css(props[i], value + 'px');
        }
      }
      if (box.attr('data-minimized')) {
        $(this).WM('minimize');
      }
    }
  });
  if (cookie) {
    $('.window[data-id="' + cookie.focused + '"]').WM('raise');
  } else {
    // Raise minimized windows for clean interfaces, so that the users know they're there
    $('.window.minimized').each(function() { $(this).WM('raise'); });
  }
}

function wmSetCookie() {
  var data = {};
  $('.window').each(function() {
    var params = { minimized: $(this).hasClass('minimized') };
    if (params.minimized) {
      var p = $(this).data('oldPos');
      params.left = p.left;
      params.top = p.top;
      params.width = p.width;
      params.height = p.height;
    } else {
      params.left = $(this).offset().left;
      params.top = $(this).offset().top;
      params.width = $(this).width();
      params.height = $(this).height();
    }
    data[$(this).attr('data-id')] = params;
  });
  data['focused'] = $('.window.focused').attr('data-id');
  $.cookie(COOKIE_NAME, data, { expires: 365 });
}

function wmInterfaceReset() {
  $.removeCookie(COOKIE_NAME);
  wmSetCoordinates();
  return false;
}
