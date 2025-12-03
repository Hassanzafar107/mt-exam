jQuery(document).ready(function($) {
    $('.delete-book').click(function() {
        var bookId = $(this).data('id');
        
        if (confirm(bm_ajax_object.confirm_message)) {
            $.ajax({
                type: 'POST',
                url: bm_ajax_object.ajax_url,
                data: {
                    action: 'delete_book',
                    book_id: bookId,
                },
                success: function(response) {
                    if (response.success) {
                        alert('Book deleted successfully.');
                        location.reload(); // Reload the page to see the changes
                    } else {
                        alert('Error deleting book: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the book.');
                }
            });
        }
    });
});
