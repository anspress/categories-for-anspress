<?php
	/**
	 * Categories page layout
	 *
	 * [Long Description.]
	 *
	 * @link http://wp3.in
	 * @since 1.0
	 *
	 * @package AnsPress
	 * @subpackage Categories for AnsPress
	 */

	global $question_categories;
?>

<div id="ap-categories" class="clearfix">
	<ul class="ap-term-category-box">
		<?php foreach($question_categories as $key => $category) : ?>
			<li class="clearfix">
				<div class="ap-term-title">
					<?php echo ap_icon('category', true) ?>
					<a class="term-title" href="<?php echo get_category_link( $category );?>">
						<?php echo $category->name; ?>
					</a>
				</div>
				
				<div class="ap-taxo-description">
					<?php
						if($category->description != '')
							echo $category->description;
						else
							_e('No description.', 'categories_for_anspress');
					?>
					<?php
						$sub_cat_count = count(get_term_children( $category->term_id, 'question_category' ));
						
						if($sub_cat_count >0){
							echo '<div class="ap-term-sub">';
							echo '<div class="sub-taxo-label">' .$sub_cat_count.' '.__('Sub Categories', 'ap') .'</div>';
							ap_sub_category_list($category->term_id);
							echo '</div>';
						}
					?>
				</div>
				<div class="ap-term-count">
					<?php printf(_n('%d Question', '%d Questions', 'categories_for_anspress', $category->count), $category->count) ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php ap_pagination(); ?>
